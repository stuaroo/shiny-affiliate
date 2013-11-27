## v.2.0.0 (November 25, 2013)
- Complete overhaul and redesign
  - Original page required postbacks after every addition/subtraction from the shopping cart, and performed Amazon cart requests on each postback as well
  - New logic uses Javascript and jQuery to add/remove from cart, and only calls 1 Amazon cart request to create the cart and forward the user to the checkout URL
  - Changed itemLookup operations to be configured based on an xml file in the filesystem (rather than hard-coded into the page), making updating products much easier
  - Redesigned page layout to take advantage of Bootstrap 3's new responsive design, and added visible cart showing contents (instead of just a link with a total amount)

## v.1.2.0 (June 8, 2013)
- Renamed AmazonApi to AmazonProductApi for clarity
- Added a changelog!
- Renamed method itemLookUpWithOffersImages to itemLookup which now takes comma-separated Response Groups as second parameter

## v.1.1.1 (April 3, 2013)
- Fixed bad logic when dealing with page refreshes (did not work correctly in all browsers)

## v.1.1.0 (March 19, 2013)
- Added methods to AmazonApi class:
  - itemLookUpWithOffersImages returns a signed URL for product information for a comma-separated list of [ASINs](http://en.wikipedia.org/wiki/Amazon_Standard_Identification_Number)
  - cartCreate returns a signed URL that builds a remote shopping cart with the specified items/quantities
  - cartAdd returns a signed URL that adds items to an existing remote shopping cart
  - cartModify returns a signed URL that allows items to be added or removed from an existing remote shopping cart
  - cartGet returns a signed URL that retrieves an existing remote shopping cart
- index.php posts-back to itself each time an add-to-cart or delete-from-cart button is clicked. Using session variables to keep track of the contents of the cart and cart identifiers, a decision tree is traversed to determine the correct operation to perform (create, add, modify, etc.). A cart checkout link appears when the user has a valid cart, which redirects user to Amazon to checkout.

## v.1.0.0
- **Initial release**
- Adapted AmazonApi class from the signed URL tutorial at: [webtutsdepot.com/2009/10/13/amazon-signed-request-php/](webtutsdepot.com/2009/10/13/amazon-signed-request-php/)