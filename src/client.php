<?php
/**
 * Created by PhpStorm.
 * User: bbaisley
 * Date: 12/11/14
 * Time: 5:17 PM
 */

namespace Bigstock;

use Shutterstock\Presto\Presto;
use Shutterstock\Presto\Response;

class Bigstock {
    public $rest = null;
    protected $url_base = 'api.bigstockphoto.com/2/';
    public $url = null;
    public $raw_response = null;
    private $account_id = null;
    private $secret_key = null;

    /**
     * @param $account_id
     * @param $secret_key
     * @param null $curl_opts
     */
    public function __construct($account_id, $secret_key, $curl_opts=null) {
       $opts = array(
            CURLOPT_NOSIGNAL=>1,
            CURLOPT_TIMEOUT_MS=>2000
        );
        if ( is_array($curl_opts) ) {
            $opts = array_merge($opts, $curl_opts);
        }
        $this->rest = new Presto($opts);
        $this->account_id = $account_id;
        $this->secret_key = $secret_key;
        $this->url_base .= $account_id.'/';
        $this->url = $this->url_base;
    }

    /**
     * @param $mode anything other than 'prod' will use 'test' mode
     */
    public function setMode($mode) {
        if ($mode=='prod') {
            $this->url = $this->url_base;
        } else {
            $this->url = 'test'.$this->url_base;
        }
    }

    /**
     * @param $params
     * @return mixed|object
     */
    public function search($params) {
        $url = 'http://' . $this->url_base . '/search/?';
        $url .= http_build_query($params);
        $r = $this->rest->get($url);
        return $this->processResponse($r);
    }

    /**
     * @param $id
     * @param string $type
     * @return mixed|object
     */
    public function getAsset($id, $type='image') {
        $url = $this->url . $type . '/' . $id;
        $r = $this->rest->get($url);
        return $this->processResponse($r);
    }

    /**
     * @param $id
     * @return mixed|object
     */
    public function getImage($id) {
        return $this->getAsset($id, 'image');
    }

    /**
     * @param $id
     * @return mixed|object
     */
    public function getVideo($id) {
        return $this->getAsset($id, 'video');
    }

    /**
     * @param string $type
     * @return mixed|object
     */
    public function getCollections($type='image') {
        $url = $this->url . $type . '/?auth_key=' . $this->generateAuthKey();
        $r = $this->rest->get($url);
        return $this->processResponse($r);
    }

    /**
     * @param $type
     * @param $id
     * @param null $params
     * @return mixed|object
     */
    public function getCollection($type, $id, $params=null) {
        if ( is_array($params) ) {
            $params['auth_key'] = $this->generateAuthKey($id);
        } else {
            $params = array('auth_key'=>$this->generateAuthKey($id));
        }
        $url = $this->url . $type . '/'. $id . '/?' . http_build_query($params);
        $r = $this->rest->get($url);
        return $this->processResponse($r);
    }

    /**
     * @return mixed|object
     */
    public function getLightboxes() {
        return $this->getCollections('lightbox');
    }

    /**
     * @param $id
     * @param null $params
     * @return mixed|object
     */
    public function getLightbox($id, $params=null) {
        return $this->getCollection('lightbox', $id, $params);
    }

    /**
     * @return mixed|object
     */
    public function getClipboxes() {
        return $this->getCollections('video');
    }

    /**
     * @param $id
     * @param null $params
     * @return mixed|object
     */
    public function getClipbox($id, $params=null) {
        return $this->getCollection('clipbox', $id, $params);
    }

    /**
     * @param string $lang
     * @return mixed|object
     */
    public function getCategories($lang="en") {
        $url = $this->url . 'categories/?language=' . $lang;
        $r = $this->rest->get($url);
        return $this->processResponse($r);
    }

    /**
     * @param $id   asset ID to purchase
     * @param $size_code    usually s, m, l, xl
     * @param string $type  image or video
     * @return mixed|object
     */
    public function getPurchase($id, $size_code, $type='image') {
        $params = array(
            $type.'_id'=>$id,
            'size_code'=>$size_code,
            'auth_key'=>$this->generateAuthKey($id)
        );
        $url = $this->url . '/purchase/?'.http_build_query($params);
        $r = $this->rest->get($url);
        return $this->processResponse($r);
    }

    /**
     * @param $download_id
     * @return string
     */
    public function getDownloadUrl($download_id) {
        $params = array(
            'auth_key'=>$this->generateAuthKey($download_id),
            'download_id'=>$download_id
        );
        $url = $this->url . 'download?' . http_build_query($params);
        return $url;
   }

    /**
     * @param $download_id
     * @return mixed|object
     */
    public function download($download_id) {
        $url = $this->getDownloadUrl($download_id);
        $r = $this->rest->get($url);
        return $this->processResponse($r);
    }

    /**
     * @param null $id
     * @return string
     */
    public function generateAuthKey($id=null) {
        if ( is_null($id) ) {
            $auth_key = sha1($this->secret_key . $this->account_id);
        } else {
            $auth_key = sha1($this->secret_key . $this->account_id . $id);
        }
        return $auth_key;
    }

    /**
     * @param $r
     * @return mixed|object
     */
    public function processResponse($r) {
        $this->raw_response = $r;
        if ( $r->meta['is_success'] ) {
            if ( strpos($r->meta['content_type'], 'json')===false ) {
                $r = $r->data;
            } else {
                $r = json_decode($r->data);
            }
        } else {
            $r = (object) array(
                'response_code'=>$r->meta['errorno'],
                'message'=>$r->meta['error'],
                'data'=>array()
            );
        }
        return $r;
    }
}