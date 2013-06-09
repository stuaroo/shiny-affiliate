<?php
/**
 * Use Amazon Product API to display products and product info from Amazon.com
 *
 * Uses Amazon's Product Advertising API to display products and product info
 * from Amazon using RESTful web requests; can also be used to build remote 
 * shopping carts to allow users to checkout directly from Amazon.com
 * @version 1.2.0
 */
class AmazonProductApi
{
	protected $_publicKey = 'xxxxxxxxxxxxxxxxxxxxxx';
	protected $_privateKey = 'xxxxxxxxxxxxxxxxxxxxxx';
	protected $_associateTag = 'xxxxxxxxxxxxxxxxxxxxxx';
	
	/**
	 * Get a signed URL
	 *  
	 * @param array $param used to build url
	 * @return array $signature returns the signed string and its components
	 */
	protected function generateSignature($param)
	{
		$signature['method'] = 'GET';
		$signature['host'] = 'webservices.amazon.com';
		$signature['uri'] = '/onca/xml';
		
		$param['Service'] = 'AWSECommerceService';
		$param['AWSAccessKeyId'] = $this->_publicKey;
		$param['AssociateTag'] = $this->_associateTag;
		$param['Timestamp'] = gmdate("Y-m-d\TH:i:s\Z");
		$param['Version'] = '2011-08-01';
		ksort($param);
		
		// URL encode key/value pairs
		foreach($param as $key => $value)
		{
			$key = str_replace("%7E", "~", rawurlencode($key));
			$value = str_replace("%7E", "~", rawurlencode($value));
			$queryParamsUrl[] = $key . '=' . $value;
		}
		
		// add ampersands between key/value pairs
		$signature['queryUrl'] = implode("&", $queryParamsUrl);
		
		// start signature creation
		$stringToSign = $signature['method'] . "\n" . $signature['host'] . "\n" . $signature['uri'] . "\n" . $signature['queryUrl'];
		$signature['string'] = str_replace("%7E", "~", rawurlencode(base64_encode(hash_hmac("sha256", $stringToSign, $this->_privateKey, TRUE))));
		return $signature;
	}
	
	/**
	 * Get signed url response
	 * 
	 * @param array $params
	 * @return string $signedUrl a query url with signature 
	 */
	 public function getSignedUrl($params)
	 {
	 	$signature = $this->generateSignature($params);
		return $signedUrl = "http://" . $signature['host'] . $signature['uri'] . '?' . $signature['queryUrl'] . '&Signature=' . $signature['string'];
	 }
	 
	 /**
	  * Get a signed url for ItemLookup Operation 
	  * 
	  * @param array $items a list of ASINs to lookup
	  * @param string $responseGroup a comma-separated string with desired response groups, defaults to Offers,Images
	  * @return string $signedUrl a query url with signature
	  */
	 public function itemLookup($items, $responseGroup = 'Offers,Images')
	 {
	 	$parameters = array(
		"ResponseGroup"=>$responseGroup,
		"Operation"=>"ItemLookup",
		"MerchantId"=>"Amazon",
		"IdType"=>"ASIN"
		);
	 	if ( is_array($items) )
		{
			$parameters['ItemId'] = implode(',', $items);
		}
	 	return $signedUrl = $this->getSignedUrl($parameters);
	 }
	 
	 /**
	  * Get a signed url for CartCreate Operation
	  * 
	  * @param array $offerId an array of OfferListingId's from ItemLookup xml file (or similar); keys should be ints
	  * @param array $qty the quantity of each offer to add to cart
	  * @return string $signedUrl a query url with signature
	  */
	 public function cartCreate($offerId, $qty)
	 {
	 	if ( is_array($offerId) && is_array($qty) )
		{
			$parameters = array(
			"Operation"=>"CartCreate"
			);
			foreach ( $offerId as $key => $value ) 
			{
				$parameters['Item.' . $key . '.OfferListingId'] = $value;
				$parameters['Item.' . $key . '.Quantity'] = $qty[$key];
			}
			return $signedUrl = $this->getSignedUrl($parameters);
		}
	 }
	 
	 /**
	  * Get signed url for CartAdd Operation
	  * 
	  * @param array $offerId an array of OfferListingId's from ItemLookup xml file (or similar); keys should be ints
	  * @param array $qty the quantity of each offer to add to cart
	  * @param string $hmac Hash Message Authentication Code returned by CartCreate that identifies a cart
	  * @param string $cartId Alphanumeric token returned by CartCreate that identifies a cart
	  * @return string $signedUrl a query url with signature
	  */
	 public function cartAdd($offerId, $qty, $hmac, $cartId)
	 {
	 	if ( is_array($offerId) && is_array($qty) )
		{
			$parameters = array(
			"Operation"=>"CartAdd",
			"HMAC"=>$hmac,
			"CartId"=>$cartId
			);
			foreach ( $offerId as $key => $value ) 
			{
				$parameters['Item.' . $key . '.OfferListingId'] = $value;
				$parameters['Item.' . $key . '.Quantity'] = $qty[$key];
			}
			return $signedUrl = $this->getSignedUrl($parameters);
		}
	 }
	 
	 /**
	  * Get signed url for CartModify Operation
	  * 
	  * @param array $cartItemId an array of CartItemId's from CartCreate response xml file; keys should be ints
	  * @param array $qty the quantity of each offer to change in cart (0 to delete)
	  * @param string $hmac Hash Message Authentication Code returned by CartCreate that identifies a cart
	  * @param string $cartId Alphanumeric token returned by CartCreate that identifies a cart
	  * @return string $signedUrl a query url with signature
	  */
	  public function cartModify($cartItemId, $qty, $hmac, $cartId)
	  {
	  	if ( is_array($cartItemId) && is_array($qty) )
		{
			$parameters = array(
			"Operation"=>"CartModify",
			"HMAC"=>$hmac,
			"CartId"=>$cartId
			);
			foreach ( $cartItemId as $key => $value ) 
			{
				$parameters['Item.' . $key . '.CartItemId'] = $value;
				$parameters['Item.' . $key . '.Quantity'] = $qty[$key];
			}
			return $signedUrl = $this->getSignedUrl($parameters);
		}
	  }
	  
	  /**
	   * Get signed url for CartGet Operation
	   * 
	   * @param string $hmac Hash Message Authentication Code returned by CartCreate that identifies a cart
	   * @param string $cartId Alphanumeric token returned by CartCreate that identifies a cart
	   * @return string $signedUrl a query url with signature
	   */
	  public function cartGet($hmac, $cartId)
	  {
		$parameters = array(
		"Operation"=>"CartGet",
		"HMAC"=>$hmac,
		"CartId"=>$cartId
		);

		return $signedUrl = $this->getSignedUrl($parameters);
	  }
}
?>