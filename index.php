<?php
session_start();
require_once 'AmazonApi.php';
require_once 'Curl.php';
$Amazon = new AmazonApi();
$Curl = new Curl();

// get a signed URL and receive response xml file with item info
$items = array('0452284694','0072230665','1616960922','0441013597','1400079292','0743269519','B00BDD1UYI','B00B5ARKC0','B0060MYM3S');
$queryUrl = $Amazon->itemLookupWithOffersImages($items);
$request = $Curl->curlString($queryUrl);
$response = simplexml_load_string($request);

// variables for titles to display and use as alt text for images, and my informative text
$titles = array( 'The Gunslinger: The Dark Tower Book 1',
                 'Oracle DB 10g PL/SQL Programming',
                 'The Emperor\'s Soul',
                 'Dune',
                 'The Traveler',
                 'The 7 Habits of Highly Effective People',
                 'Adventure Time: Season 2',
                 'Fringe: Season 5',
                 'Game of Thrones: Season 2');
$info = array('Just finished, actually.  My book club decided to start reading Stephen King\'s The Dark Tower series.  
                I have mixed feelings of the book/series so far, as the pacing is kind of odd in this first book (i.e.
                a decent amount of action, followed by not much action as all).  I\'ll see how Book 2 goes.',
              'This one is for class, and is not the most exciting read in the world.  It is kind of interesting to 
                see things from a procedural programming point of view while coding PL/SQL (which forces you to think 
                about things a little differently than you would with a typically object-oriented mindset).',
              'Got my copy at a release event and signed by the author, Brandon Sanderson.  Haven\'t got a chance to 
                crack it open and get absorbed in this short novel as of yet, but I think the time is now and I\'ll start 
                \'er up this weekend',
              'Great sci-fi title that sucked me in when I first read it in my youth.  I need to give it a re-read
                sometime soon, to see how much I can and cannot remember.  The sequels are kind of meh, but this first
                book is definitely worth reading.',
              'I read this novel based on recommendations from several of my co-workers, and it was a good read.  I 
                liked the paranoid feel of the book, and some of the odd facts about the author (e.g. that he has never
                 met his editor and that they communicate through the Internet and via an untraceable satellite phone).',
              'This book gets a bad wrap (which is part of the reason why it took me so long to read it), but it shouldn\'t.  
                Covey has come up with some interesting principles that at least deserver a cursory read-through.  My 
                personal favorite: seek first to understand, then to be understood.',
              'Well, I\'m not watching it quite yet as it has not been released, but I do watch the show all the time on 
                Cartoon Network, so in that respect I could very well have seen the entirety of this season.  Great show, 
                great characters; guaranteed one of the weirdest shows you will ever see (and I can\'t recommend it enough!)',
              'Wish I would have watched season 5 while it aired, but now I\'ll just have to wait a few months for the 
                DVD\'s to release so I can sit back and watch how the show ends',
              'Just finished a season 2 re-watch with a friend of mine, and I\'m rather excited for season 3 to start
                this week!');
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
    <title>OnePageStore &middot; Stuart Baker</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Shop for a few items that I find awesome, and let Amazon.com send them to you.">
    <meta name="author" content="Stuart Baker">

    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/bootstrap-responsive.min.css" rel="stylesheet">
    <link href="../css/fountless.css" rel="stylesheet">

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
                  <li><a href="../index.html#onepage">OnePageStore</a></li>
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

    <!-- Thumbnails
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
              <img src="<?php echo $item->LargeImage->URL; ?>" width="200" alt="<?php echo $titles[$i] . ' cover'; ?>">
              <div class="caption">
                <h5><?php echo $titles[$i]; ?></h5>
                <p><?php echo $info[$i]; $i++; ?></p>
                <p>Amazon Price: <span class="ama-price"><?php echo $item->Offers->Offer->OfferListing->Price->FormattedPrice; ?></span></p>
                <button type="submit" name="asin" value="<?php echo $currAsin = 'a' . (string)$item->ASIN;; ?>" 
                <?php // set button value to ASIN#, and change button text based on existence of session variable
                echo (isset($_SESSION[$currAsin]) ? 'class="btn btn-danger"><i class="icon-minus icon-white"></i> from ' : 
                  'class="btn btn-success"><i class="icon-plus icon-white"></i> to '); ?>Amazon Cart</button>
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
    <script src="../js/bootstrap.min.js"></script>
  </body>
</html>