<?php
/**
 * Use Amazon API to display products and product info 
 *
 * Found at webtutsdepot.com/2009/10/13/amazon-signed-request-php/ with some
 * alterations to variable/class names.  3-19-13 SB adding more methods
 * 
 */
class AmazonApi
{
	protected $_publicKey = 'AKIAJMB6F4WQQWW7234A';
	protected $_privateKey = '3JE0ssQ1ix0xMESutXfc02DmkjdW1XD76oRbzllb';
	protected $_associateTag = 'fountless-20';
	
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
		
		foreach($param as $key => $value)
		{
			$key = str_replace("%7E", "~", rawurlencode($key));
			$value = str_replace("%7E", "~", rawurlencode($value));
			$queryParamsUrl[] = $key . '=' . $value;
		}
		
		// append all "params=value"s with ampersands
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
	  * Get a signed url for ItemLookup Operation with Offers Response Group
	  * 
	  * @param array $items a list of ASINs to lookup
	  * @return string $signedUrl a query url with signature
	  */
	 public function itemLookupWithOffersImages($items)
	 {
	 	$parameters = array(
		"ResponseGroup"=>"Offers,Images",
		"Operation"=>"ItemLookup",
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