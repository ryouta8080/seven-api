<?php
/**
 * Seven Affiliate API class.
 *
 * @author Ryouta
 *
 * @link https://github.com/ryouta8080/seven-api/
 *
 * @license MIT License
 *
 * @see https://7af.omni7.jp/af_static_site/API1.html For more information about the configuration
 */
class SevenApi {

	const API_CATEGORY = 'https://7af-api.omni7.jp/af_api/affiliate/rest/GetShoppingCategory';
	const RES_CATEGORY = 'GetShoppingCategoryResponse';
	const API_PRODUCT = 'https://7af-api.omni7.jp/af_api/affiliate/rest/SearchProduct';
	const RES_PRODUCT = 'SearchProductResponse';
	const API_RANKING = 'https://7af-api.omni7.jp/af_api/affiliate/rest/SearchRanking';
	const RES_RANKING = 'SearchRankingResponse';
	const API_REVIEW = 'https://7af-api.omni7.jp/af_api/affiliate/rest/SearchProductReview';
	const RES_REVIEW = 'SearchProductReviewResponse';

	const INTERVAL_SECOND = 1.2;
	protected static $lastTime = 0;
	
	protected $apiUserId = false;
	protected $secretKey = false;
	protected $commonConfig = [];
	protected $resultAmount = 50;
	protected $isDebug = false;
	protected $isSafelyConnect = true;

	public function __construct($apiUserId, $secretKey, $isDebug=false){
		$this->apiUserId = $apiUserId;
		$this->secretKey = $secretKey;
		$this->isDebug = $isDebug;
	}
	
	public function setConfig( Array $config = [] ){
		$this->commonConfig = $config;
	}
	public function setDefaultResultAmount( $resultAmount ){
		$this->resultAmount = $resultAmount;
	}
	public function setSafelyConnect( $isSafelyConnect ){
		$this->isSafelyConnect = $isSafelyConnect;
	}
	
	public function category(Array $param=[], $omitResponseMessage=true){
		$responseMessage = $omitResponseMessage?static::RES_CATEGORY:false;
		return $this->getContent(static::API_CATEGORY, $param, false, $responseMessage);
	}
	
	public function product(Array $param=[], $page=false, $omitResponseMessage=true){
		$responseMessage = $omitResponseMessage?static::RES_PRODUCT:false;
		return $this->getContent(static::API_PRODUCT, $param, $page, $responseMessage);
	}
	
	public function ranking(Array $param=[], $page=false, $omitResponseMessage=true){
		$responseMessage = $omitResponseMessage?static::RES_RANKING:false;
		return $this->getContent(static::API_RANKING, $param, $page, $responseMessage);
	}
	
	public function review(Array $param=[], $page=false, $omitResponseMessage=true){
		$responseMessage = $omitResponseMessage?static::RES_REVIEW:false;
		return $this->getContent(static::API_REVIEW, $param, $page, $responseMessage);
	}
	
	protected function getContent($apiUrl, $param, $page=false, $responseMessage=false){
		$current = microtime(true);
		$diffTime = $current - self::$lastTime;
		if($this->isSafelyConnect && $diffTime < static::INTERVAL_SECOND){
			$usleep = floor( static::INTERVAL_SECOND*1000000 - $diffTime*1000000 );
			usleep($usleep);
		}
		
		$config = $this->buildParam($apiUrl, $param, $page);
		$url = $apiUrl . '?' . http_build_query($config);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url );
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$content = curl_exec($curl);
		$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$errno = curl_errno($curl);
		$error = curl_error($curl);
		curl_close($curl);
		
		self::$lastTime = microtime(true);

		$res = new \stdClass;
		$res->code = $code;
		$res->isOk = false;
		$res->errorMessage = null;
		$res->errorStatus = null;
		if($this->isDebug){
			$res->url = $url;
			$res->param = $config;
		}
		$res->response = null;

		$json = json_decode($content);
		$res->response = $json;
		
		if (CURLE_OK !== $errno) {
			$res->errorMessage = $error;
			$res->errorStatus = $errno;
			return $res;
		}
		
		if($res->code == 200){
			$res->isOk = true;
			if($responseMessage){
				if( is_string($responseMessage) && isset($res->response->{$responseMessage})){
					$res->response = $res->response->{$responseMessage};
					//TotalAmountが1の時ProductやProductReviewが配列となるように変更する
					if(isset($res->response->TotalAmount) && $res->response->TotalAmount==1){
						if(isset($res->response->Products->Product) && ! is_array($res->response->Products->Product) ){
							$res->response->Products->Product = array($res->response->Products->Product);
						}
						if(isset($res->response->ProductReviews->ProductReview) && ! is_array($res->response->ProductReviews->ProductReview) ){
							$res->response->ProductReviews->ProductReview = array($res->response->ProductReviews->ProductReview);
						}
					}
				}else{
					if(is_string($responseMessage)){
						trigger_error('SevenApi::getContent - レスポンスデータに指定された$responseMessage "'.$responseMessage.'" は見つかりませんでした',E_USER_WARNING);
					}else{
						trigger_error('SevenApi::getContent - 指定された$responseMessageに文字列以外の値が指定されています',E_USER_WARNING);
					}
				}
			}
		}
		if(isset($res->response->ApiError)){
			$res->errorMessage = $res->response->ApiError->ApiErrorMessage;
			$res->errorStatus = $res->response->ApiError->ApiErrorStatus;
		}
		
		return $res;
	}
	
	protected function buildParam($url, Array $param, $page=false){
		$config = array(
			'ApiUserId' => $this->apiUserId,
			'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
			'ResponseFormat' => 'JSON',
		);
		if($page){
			$config['StartIndex'] = ($this->resultAmount * ($page-1) ) + 1;
			$config['ResultAmount'] = $this->resultAmount;
		}
		$config = array_merge($config, $this->commonConfig, $param);
		$signature = $this->createSignature($url, $config);
		$config['Signature'] = $signature;
		return $config;
	}
	protected function createSignature($url, Array $param){
		ksort($param);
		$pair = array();
		foreach ($param as $key => $value) {
			array_push($pair, $key.'='.$value);
		}
		$stringToSign = rawurlencode( 'GET|'. $url . '|' . join('|', $pair) );
		$signature = base64_encode(hash_hmac('sha256', $stringToSign, $this->secretKey, true));
		return $signature;
	}

}
