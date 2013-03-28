<?php
/**
 * Perform curl operations without having to remember all curl options
 * 
 * Method curlString found in the comments at http://www.php.net/manual/en/curl.examples.php
 * but this or similar code pieces I've seen at various places around the web. 
 */
class Curl
{
	/**
	 * Retrieves a file using curl and provides a string
	 * 
	 * @param string $url location of file
	 * @return string $output the file as a string from the url provided
	 */
	public function curlString($url)
	{
		// create curl resource
        $ch = curl_init();

        // set url
        curl_setopt($ch, CURLOPT_URL, $url);

        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // $output contains the output string
        $output = curl_exec($ch);

        // close curl resource to free up system resources
        curl_close($ch);
		
		return $output;
	}
}
?>