<?php
session_start();
require_once 'AmazonApi.php';
require_once 'Curl.php';
$Amazon = new AmazonApi();
$Curl = new Curl();

// get a signed URL and receive response xml file with item info
$items = array('0765350386','1430210117','0765350378','076533433X','1576759776','0061092177','B00BDD1UYI','B00B5ARKC0','B0060MYM3S','B000A59PMO');
$queryUrl = $Amazon->itemLookupWithOffersImages($items);
$request = $Curl->curlString($queryUrl);
$response = simplexml_load_string($request);

// variables for titles to display and use as alt text for images, and my informative text
$titles = array('Mistborn: The Final Empire',
                 'PHP Object-Oriented Solutions',
                 'Elantris',
                 'The Eye of the World',
                 'Leadership and Self-Deception',
                 'Small Gods',
                 'Adventure Time: Season 2',
                 'Fringe: Season 5',
                 'Game of Thrones: Season 2',
                 'Veronica Mars: Season 1');
$info = array('This has been at the top of my \'To Read\' list since I started <em>The Wheel of Time</em> a year ago 
                (and I would\'ve been a fool to start another series in the middle of that one and have any hope of 
                keeping the characters separate).  I\'m quite liking it so far!',
              'Currently taking a course on PHP and this is the textbook.  So far I\'ve liked the way the author has 
                presented <abbr title="Object-Oriented Programming" class="initialism">OOP</abbr> concepts; I\'ll 
                have to wait and see how the rest of the content and examples turn out before passing judgement.',
              'The first Brandon Sanderson book I read came highly recommended by a good friend, and now I highly 
                recommend it to others.  I love that this  world is developed in one book with great characters and 
                an intriguing magic system.',
              'Book 1 of <em>The Wheel of Time</em> is a great read, and was just the beginning of my recent 
                year-long experience of the entire series.  Loved this book and loved the series, so I can\'t help 
                but recommend it to all.  I am, however, puzzled by this cover, as it appears Rand is on a boat mast 
                but I can\'t remember him on a boat during this book',
              'Not only a great business book but a great book about life in general.  The main points are rather 
                simple and seemingly obvious, but they have far-reaching implications if you "live" the material, 
                as the authors note.',
              'Great book by one of my favorite authors, Terry Pratchett.  Particularly good if you are able to see
                the lighter side of organized religion.  You may want to consider a different title if this is your
                first Discworld book however (I would recommend Guards! Guards!)',
              'Well, I\'m not watching it yet so to speak as it has yet to be released.  Great show, great characters
                Can\'t recommend it enough!',
              'Wish I would have watched season 5 while it aired, but now I\'ll just have to wait a few months for the 
                DVD\'s to release so I can sit back and watch how the show ends',
              'Just finished the season 2 re-watch with a friend of mine, and I\'m rather excited for season 3 to start
                in a week!',
              'A friend just forced this series on me, but it has been recommended to me by several individuals.  Perhaps
                soon I can do some recommending as well');
// variable to hold signed URL from one of the operations below, variable for purchase link, and setting cart count
$opUrl = '';
$purchaseUrl = '';
$cartCount = isset($_SESSION['CartCount']) ? (int)$_SESSION['CartCount'] : 0;
// check if this is a browser refresh; if it is and session CartId is set, get the cart (cartGet())
if ( isset($_SERVER['HTTP_CACHE_CONTROL']) && $_SERVER['HTTP_CACHE_CONTROL'] === 'max-age=0' )
{
	if ( isset($_SESSION['CartId']) )
	{
		$opUrl = $Amazon->cartGet($_SESSION['HMAC'], $_SESSION['CartId']);
	}
	// if this is a refresh with no session CartId, no buttons have been clicked so unset opUrl and purchaseUrl
	else
	{
		unset($opUrl, $purchaseUrl);
	}
}
// if POST['ASIN'] exists: either need to create a cart, add to an existing cart, or delete from a cart
elseif ( isset($_POST['asin']) )
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
// if this is not a refresh or a POST, then it's first load; load default purchase buttons and unset opUrl and purchaseUrl
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
    <title>Amazon Product Advertising API Testing with PHP - Take 7</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-responsive.min.css" rel="stylesheet">
    <link href="css/fountless.css" rel="stylesheet">

  	<link href="http://fonts.googleapis.com/css?family=Lobster|Cabin" rel="stylesheet" type="text/css">

    <!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->
  </head>
  <body>

    <!-- NAVBAR
    ================================================== --> 
    <div class="navbar navbar-fixed-top navbar-inverse">  
      <div class="navbar-inner">  
        <div class="container">
          <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </a>
          <ul class="nav">
            <li class="active"><a class="brand" href="../index.html">Stuart Baker</a></li>
          </ul>
          <div class="nav-collapse collapse">
            <ul class="nav">   
              <li><a href="../index.html#about"><i class="icon-home icon-white"></i> About</a></li>  
              <li><a href="#"><i class="icon-book icon-white"></i> Store</a></li>
              <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="icon-wrench icon-white"></i> Projects<b class="caret"></b></a>
                <ul class="dropdown-menu">  
                  <li><a href="../index.html#circ">Circulator</a></li>  
                  <li><a href="../index.html#elimi">Elimi-wait</a></li>  
                  <li><a href="../index.html#cube">Cube Training</a></li> 
                </ul>
              </li>
            <?php   // show cart checkout link if a purchase URL is available
            if ( isset($purchaseUrl) ): ?> 
              <li><a href="<?php echo $purchaseUrl; ?>" target="_blank"><i class="icon-shopping-cart icon-white"></i> Cart Checkout <span class="badge"><?php echo $cartCount ?></span></a></li>
            <?php endif; ?> 
            </ul>  
          </div>  
        </div>  
      </div>  
    </div>
    <div class="ama-checkout"><i class="icon-arrow-up"></i> Click this link when you're ready to checkout!</div>

    <!-- Sidebar & Thumbnails
    ================================================== -->
    <div class="container store">
      <form method="post">
        <h3>What I'm Reading</h3>
        <ul class="thumbnails">
      <?php // display each item with image from original request
      $i = 0;
      foreach ( $response->Items->Item as $item ): ?> 
      <?php if ( $i == 3 ): ?> 
        </ul>
          <h3>I Highly Recommend</h3>
        <ul class="thumbnails">
      <?php elseif ( $i == 6 ): ?> 
        </ul>
          <h3>What I'm Watching</h3>
        <ul class="thumbnails">
      <?php endif; ?> 
          <li class="span4">
            <div class="thumbnail">
              <img src="<?php echo $item->LargeImage->URL; ?>" width="200" alt="<?php echo $titles[$i] . ' cover'; ?>"><br>
              <div class="caption">
                <h5><?php echo $titles[$i]; ?></h5>
                <p><?php echo $info[$i]; $i++; ?></p>
                <p>Amazon Price: <span class="ama-price"><?php echo $item->Offers->Offer->OfferListing->Price->FormattedPrice; ?></span></p>
                <button type="submit" name="asin" value="<?php echo $currAsin = 'a' . (string)$item->ASIN;; ?>" 
                <?php // set button value to ASIN#, and change button text based on existence of session variable
                echo (isset($_SESSION[$currAsin]) ? 'class="btn btn-danger"><i class="icon-minus icon-white"></i> from ' : 
                  'class="btn btn-success"><i class="icon-plus icon-white"></i> to '); ?>Amazon Cart</button><br><br>
              </div>
            </div>
          </li>
      <?php endforeach; ?> 
        </ul>
      </form>

      <footer>
        <p class="pull-right"><a href="#">Back to top</a></p>
        <p>&copy; 2012-2013 Stuart Baker &middot; Icons courtesy of <a href="http://glyphicons.com/">Glyphicons</a></p>
      </footer>

    </div><!-- /.container -->

    <script src="http://code.jquery.com/jquery-1.8.3.min.js"></script>
    <script>
    <?php   // if purchaseUrl is not available, hide help text; otherwise show help text so user knows how to checkout
      if ( isset($purchaseUrl) ): ?> 
      $(document).ready(function() {
        $(".ama-checkout").fadeOut(5000);
      });
    <?php else: ?> 
      $(document).ready(function() {
        $(".ama-checkout").hide();
      });
    <?php endif; ?> 
    </script>
    <script src="js/bootstrap.min.js"></script>
  </body>
</html>