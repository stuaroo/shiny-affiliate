<?php
session_start();
require_once 'AmazonProductApi.php';
require_once 'Curl.php';

$Amazon = new AmazonProductApi();
$Curl = new Curl();

// array of ASINs to get product info for
$items = array('0452284694','0072230665','0812550706','B00BUADSMQ','0765325950','B0096KEMUY');
// type of information we want to get back
$responseGroup = 'Offers,Images,ItemAttributes';
// get a signed URL to lookup product info; pull in info using curl
$queryUrl = $Amazon->itemLookup($items, $responseGroup);
$request = $Curl->curlString($queryUrl);
$response = simplexml_load_string($request);

// variable to hold signed URL from one of the operations below, variable for purchase link, and setting cart count
$opUrl = '';
$purchaseUrl = '';
$cartCount = isset($_SESSION['CartCount']) ? (int)$_SESSION['CartCount'] : 0;
// if POST['ASIN'] exists: either need to create a cart, add to an existing cart, or delete from a cart
if ( isset($_POST['asin']) )
{
	// For add, create and delete
	$searchTerm = substr($_POST['asin'], 1);
	$key = array_search($searchTerm, $items);
	// For add & create: set offerId array to correct item's id, and set qty array to 1 
	$offerId = array($key => $response->Items->Item[$key]->Offers->Offer->OfferListing->OfferListingId);
	$qty = array($key => 1);
	// if a cart ID exists (SESSION['CartId']), then add or delete from it
	if ( isset($_SESSION['CartId']) )
	{
		// if ASIN is in session variable (SESSION['a#']), delete from cart (cartModify()); Had to add 'a' to asin (can't use numeric-only key)
		$findAsin = 'a' . $items[$key];
		if ( isset($_SESSION[$findAsin]) )
		{
			// For delete: change qty to 0 and set cartItemId array to correct id from session variable
			$cartItemId = array($key => $_SESSION[$findAsin]);
			$qty[$key] = 0;
			$opUrl = $Amazon->cartModify($cartItemId, $qty, $_SESSION['HMAC'], $_SESSION['CartId']);
			// unset session variable for the ASIN and remove 1 from cart
			unset($_SESSION[$findAsin]);
			$cartCount--;
		}
		// else add to cart (cartAdd())
		else 
		{
			$opUrl = $Amazon->cartAdd($offerId, $qty, $_SESSION['HMAC'], $_SESSION['CartId']);
			$cartCount++;
		}
	}
	// if no cart ID exists, create a cart (createCart())
	else 
	{
		$opUrl = $Amazon->cartCreate($offerId, $qty);
		$cartCount++;
	}
}
// if this is not a POST, then it's first load; load default purchase buttons and unset opUrl and purchaseUrl
else 
{
	unset($opUrl, $purchaseUrl);
}

// if an operation is needed, load the response and set purchase url and session variables 
if ( isset($opUrl) )
{
	$cartRequest = $Curl->curlString($opUrl);
	$checkout = simplexml_load_string($cartRequest);
	$purchaseUrl = $checkout->Cart->PurchaseURL;
	$_SESSION['HMAC'] = (string)$checkout->Cart->HMAC;
	$_SESSION['CartId'] = (string)$checkout->Cart->CartId;
	$_SESSION['CartCount'] = $cartCount;
	// if cart has items, set ASIN session variables if item is in cart
	if ( $cartCount > 0 )
	{
		foreach ($checkout->Cart->CartItems->CartItem as $cartItem) 
		{
			$setAsin = 'a' . (string)$cartItem->ASIN;
			if ( !isset($_SESSION[$setAsin]) )
			{
				$_SESSION[$setAsin] = (string)$cartItem->CartItemId;
			}
		}
	}
	// otherwise last item removed from cart, delete cart purchase link
	else
	{
		unset($purchaseUrl);
	}
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Store Test</title>
	<link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/css/bootstrap-combined.min.css" rel="stylesheet">
    <!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
  </head>
  <body>
  	<!-- NAVBAR -->
    <div class="navbar navbar-fixed-top navbar-inverse">  
      <div class="navbar-inner">  
        <div class="container">
          <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </a>
          <ul class="nav">
            <li class="active"><a class="brand" href="../index.html">Test Store</a></li>
          </ul>
          <div class="nav-collapse collapse">
            <ul class="nav">   
              <li><a href="../index.html#about"><i class="icon-home icon-white"></i> About</a></li>  
              <li><a href="#"><i class="icon-book icon-white"></i> Store</a></li>
            <?php   // show cart checkout link if a purchase URL is available
            if ( isset($purchaseUrl) ): ?> 
              <li><a href="<?php echo $purchaseUrl; ?>" target="_blank"><i class="icon-shopping-cart icon-white"></i> Cart Checkout <span class="badge"><?php echo $cartCount ?></span></a></li>
            <?php endif; ?> 
            </ul>  
          </div>  
        </div>  
      </div>  
    </div>
    <!-- THUMBNAILS -->
    <div class="container store">
      <form method="post">
      	<h1 style="margin-top: 50px">Buy some great stuff from Amazon!</h1>
        <ul class="thumbnails">
<?php foreach ( $response->Items->Item as $item ):?>
			<li class="span4">
            	<div class="thumbnail">
					<img src="<?php echo $item->LargeImage->URL; ?>" width="200" alt="<?php echo $item->ItemAttributes->Title; ?> cover">
					<h5><?php echo $item->ItemAttributes->Title; ?></h5>
					<p>Amazon Price: 
						<?php echo $item->Offers->Offer->OfferListing->Price->FormattedPrice; ?>
					</p>
					<button type="submit" name="asin" value="<?php echo $currAsin = 'a' . $item->ASIN; ?>"
					<?php // set button value to ASIN#, and change button text based on existence of session variable
	                echo (isset($_SESSION[$currAsin]) ? 'class="btn btn-danger"><i class="icon-minus icon-white"></i> from ' : 
	                  'class="btn btn-success"><i class="icon-plus icon-white"></i> to '); ?>Amazon Cart</button>
				</div>
			</li>
<?php endforeach; ?>
		</ul>
	  </form>
	</div>
<script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/js/bootstrap.min.js"></script>
</body>