# OnePageStore

- Uses PHP class called AmazonProductApi to set proper parameters and create signed URL's for use with Amazon's Product Advertising API
- Also makes use of a very basic cURL class to retrieve response XML file
- Then, on one page, user can view products, add them to a remote cart (or remove something they've already added), and finally click the Purchase URL link to checkout at Amazon proper.

## Notes about Response Groups

When performing an ItemLookup operation to the Amazon Product Advertising API, you must specify Response Group(s) (hereafter 'RG'), which signify the type of information you want to get back.

Examples: 
  - Images RG will return URL's to images of a particular product, 
  - ItemAttributes RG will return product details like Manufacturer, Author, Title, etc.
  - Offers RG will (generally) return information on pricing and availability from particular merchants of a product

There are some exceptions, specifically when you want to display current pricing:
  - When multiple sizes or types of a product are for sale (Ex: clothing, shoes), the Offers RG will not return price information, you must instead use the Variations RG
  - There is not a way to display current pricing for Kindle eBooks through the Product Advertising API; you can show the List Price or other eBook information by using the ItemAttributes RG, but keep in mind that List Price is not the same as current selling price

Also note: **Digital items, like Kindle eBooks and mp3's, cannot be added to a remote shopping cart** [per Amazon](http://docs.aws.amazon.com/AWSECommerceService/latest/DG/ShoppingCartConcepts.html#ItemsThatCannotBeAddedtotheActiveCartArea)

## Additional Resources

[Product Advertising API Scratchpad](http://associates-amazon.s3.amazonaws.com/scratchpad/index.html) - great for testing which elements are returned by each RG

[Product Advertising API Quick Reference Card](http://s3.amazonaws.com/awsdocs/Associates/2011-08-01/prod-adv-api-qrc-2011-08-01.pdf) - lists required/optional parameters as well as relevant RG for each operation
