<?php
/**
 * Bigstock API Client
 *
 * @package   Bigstock
 * @author    Brent Baisley <bbaisley@shutterstock.com>
 * @copyright 2014 Brent Baisley
 * @license   http://opensource.org/licenses/MIT MIT License
 */

namespace Bigstock;

use Shutterstock\Presto\Presto;
use Shutterstock\Presto\Response;

class Bigstock
{
    /**
     * REST Client
     *
     * @var Presto
     */
    public $rest = null;

    /**
     * The base URL for API requests
     *
     * @var string
     */
    protected $url_base = 'api.bigstockphoto.com/2/';

    /**
     * Currently set URL for API requests (based on environment)
     *
     * @var string
     */
    public $url = null;

    /**
     * Response from the last API call
     *
     * @var mixed
     */
    private $raw_response = null;

    /**
     * API Account ID
     *
     * @var string
     */
    private $account_id = null;

    /**
     * API Secret Key
     *
     * @var string
     */
    private $secret_key = null;

    /**
     * Constructor
     *
     * @param string $account_id
     * @param string $secret_key
     * @param string $mode
     * @param array  $curl_opts
     */
    public function __construct($account_id, $secret_key, $mode = 'prod', $curl_opts = array())
    {
        $opts = array(
            CURLOPT_NOSIGNAL => 1,
            CURLOPT_TIMEOUT_MS => 2000
        );
        if (!empty($curl_opts)) {
            $opts = array_merge($opts, $curl_opts);
        }
        $this->rest = new Presto($opts);
        $this->account_id = $account_id;
        $this->secret_key = $secret_key;
        $this->url_base .= $account_id . '/';
        $this->setMode($mode);
    }

    /**
     * Set client mode (test or prod)
     *
     * @param string $mode anything other than 'prod' will use 'test' mode
     */
    public function setMode($mode)
    {
        $this->url = 'http://' . ($mode == 'prod' ? '' : 'test') . $this->url_base . $this->account_id . '/';
    }

    /**
     * API Call: search
     *
     * @see http://help.bigstockphoto.com/hc/en-us/articles/200303245-API-Documentation#search
     *
     * @param array $params
     * @return mixed
     */
    public function search(array $params)
    {
        $url = $this->url . 'search/?' . http_build_query($params);
        $r = $this->rest->get($url);
        return $this->processResponse($r);
    }

    /**
     * API Call: get asset
     *
     * @param string $id
     * @param string $type
     * @return mixed
     */
    public function getAsset($id, $type = 'image')
    {
        $url = $this->url . $type . '/' . $id;
        $r = $this->rest->get($url);
        return $this->processResponse($r);
    }

    /**
     * API Call: get image
     *
     * @see http://help.bigstockphoto.com/hc/en-us/articles/200303245-API-Documentation#image-detail
     *
     * @param string $id
     * @return mixed
     */
    public function getImage($id)
    {
        return $this->getAsset($id, 'image');
    }

    /**
     * API Call: get video
     *
     * @param string $id
     * @return mixed
     */
    public function getVideo($id)
    {
        return $this->getAsset($id, 'video');
    }

    /**
     * API Call: get collections
     *
     * @param string $type
     * @return mixed
     */
    public function getCollections($type = 'image')
    {
        $url = $this->url . $type . '/?auth_key=' . $this->generateAuthKey();
        $r = $this->rest->get($url);
        return $this->processResponse($r);
    }

    /**
     * API Call: get collections
     *
     * @param string $type
     * @param string $id
     * @param array  $params
     * @return mixed
     */
    public function getCollection($type, $id, array $params = array())
    {
        $params['auth_key'] = $this->generateAuthKey($id);
        $url = $this->url . $type . '/' . $id . '/?' . http_build_query($params);
        $r = $this->rest->get($url);
        return $this->processResponse($r);
    }

    /**
     * API Call: get lightboxes
     *
     * @see http://help.bigstockphoto.com/hc/en-us/articles/200303245-API-Documentation#lightbox
     *
     * @return mixed
     */
    public function getLightboxes()
    {
        return $this->getCollections('lightbox');
    }

    /**
     * API Call: get lightbox
     *
     * @see http://help.bigstockphoto.com/hc/en-us/articles/200303245-API-Documentation#lightbox
     *
     * @param string $id
     * @param array  $params
     * @return mixed
     */
    public function getLightbox($id, array $params = array())
    {
        return $this->getCollection('lightbox', $id, $params);
    }

    /**
     * API Call: get clipboxes
     *
     * @return mixed
     */
    public function getClipboxes()
    {
        return $this->getCollections('video');
    }

    /**
     * API Call: get clipbox
     *
     * @param string $id
     * @param array  $params
     * @return mixed
     */
    public function getClipbox($id, array $params = array())
    {
        return $this->getCollection('clipbox', $id, $params);
    }

    /**
     * API Call: get categories
     *
     * @see http://help.bigstockphoto.com/hc/en-us/articles/200303245-API-Documentation#categories
     *
     * @param string $lang
     * @return mixed
     */
    public function getCategories($lang = "en")
    {
        $url = $this->url . 'categories/?language=' . $lang;
        $r = $this->rest->get($url);
        return $this->processResponse($r);
    }

    /**
     * API Call: get purchase
     *
     * @see http://help.bigstockphoto.com/hc/en-us/articles/200303245-API-Documentation#purchase
     *
     * @param string $id        asset ID to purchase
     * @param string $size_code usually s, m, l, xl
     * @param string $type      image or video
     * @return mixed
     */
    public function getPurchase($id, $size_code, $type = 'image')
    {
        $params = array(
            $type . '_id' => $id,
            'size_code' => $size_code,
            'auth_key' => $this->generateAuthKey($id)
        );
        $url = $this->url . '/purchase/?' . http_build_query($params);
        $r = $this->rest->get($url);
        return $this->processResponse($r);
    }

    /**
     * API Call: get download URL
     *
     * @see http://help.bigstockphoto.com/hc/en-us/articles/200303245-API-Documentation#download
     *
     * @param string $download_id
     * @return string
     */
    public function getDownloadUrl($download_id)
    {
        $params = array(
            'auth_key' => $this->generateAuthKey($download_id),
            'download_id' => $download_id
        );
        $url = $this->url . 'download?' . http_build_query($params);
        return $url;
    }

    /**
     * API Call: get download
     *
     * @see http://help.bigstockphoto.com/hc/en-us/articles/200303245-API-Documentation#download
     *
     * @param string $download_id
     * @return mixed
     */
    public function download($download_id)
    {
        $url = $this->getDownloadUrl($download_id);
        $r = $this->rest->get($url);
        return $this->processResponse($r);
    }

    /**
     * Generate an API auth key
     *
     * @param null|string $id
     * @return string
     */
    public function generateAuthKey($id = null)
    {
        if (is_null($id)) {
            $auth_key = sha1($this->secret_key . $this->account_id);
        } else {
            $auth_key = sha1($this->secret_key . $this->account_id . $id);
        }
        return $auth_key;
    }

    /**
     * Process / decode the raw response
     *
     * @param Response $r
     * @return mixed
     */
    public function processResponse($r)
    {
        $this->raw_response = $r;
        if ($r->meta['is_success']) {
            if (strpos($r->meta['content_type'], 'json') === false) {
                $r = $r->data;
            } else {
                $r = json_decode($r->data);
            }
        } else {
            $r = (object)array(
                'response_code' => $r->meta['errorno'],
                'message' => $r->meta['error'],
                'data' => array()
            );
        }
        return $r;
    }
}