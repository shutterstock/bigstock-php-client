bigstock-php-client
===================

PHP Client for the Bigstock API. Full documentation is available at [http://help.bigstockphoto.com/hc/en-us/articles/200303245-API-Documentation](http://help.bigstockphoto.com/hc/en-us/articles/200303245-API-Documentation). 
Self-serve signup for API accounts are available in the [Partners section](https://www.bigstockphoto.com/partners) of Bigstock.

Install
-------
Install the Bigstock PHP Client with Composer.

    "require": {
        "bigstock/bigstock": "0.1"
    },


Usage
-----

Create an instance of the Bigstock API Client by passing in your API ID and API Secret as parameters. 
The client will then handle any authentication when required.

    $bigstock = new Bigstock\Bigstock('API ID', 'API Secret');
    

### Perform a search and check for a successful result

    $search_params = array('q'=>'dog');
    $result = $bigstock->search( $search_params );
    if ($result->message == 'success') {
        $pages = $result->data->paging;
        $images = $result->data->images;
    }

### Loop through search results and create HTML to display images

    $html = '';
    foreach( $images as $image ) {
        $html .= "<img src='{$image->small_thumb->url}' title='{$image->title}' height='{$image->small_thumb->height}' width='{$image->small_thumb->width}'>";
    }
    echo $html;

### Get detailed information about an image

    $result = $bigstock->getImage(22411445);
    if ($result->message == 'success') {
        $formats = $result->data->image->formats;
        $preview_url = $result->data->image->preview->url;
    }

### Purchase and download an image

    $result = $bigstock->getPurchase(22411445, 'l');
    if ($result->message == 'success') {
        // Get the URL to the file to download separately
        $file_url = $bigstock->getDownloadUrl($result->data->download_id);
        // Or download the file directly
        $file = $bigstock->download($result->data->download_id);
    }

