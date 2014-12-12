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

    public function __construct($account_id, $secret_key, $curl_opts=null) {
       $opts = array(
            CURLOPT_NOSIGNAL=>1,
            CURLOPT_TIMEOUT_MS=>1000
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

    public function setMode($mode) {
        if ($mode=='prod') {
            $this->url = $this->url_base;
        } else {
            $this->url = 'test'.$this->url_base;
        }
    }

    /**
     * @param $params
     */
    public function search($params) {
        $url = 'http://' . $this->url_base . '/search/?';
        $url .= http_build_query($params);
        $r = $this->rest->get($url);
        return $this->processResponse($r);
    }

    public function getAsset($id, $type='image') {
        $url = $this->url . $type . '/' . $id;
        $r = $this->rest->get($url);
        return $this->processResponse($r);
    }

    public function getImage($id) {
        return $this->getAsset($id, 'image');
    }

    public function getVideo($id) {
        return $this->getAsset($id, 'video');
    }

    public function getCollections($type='image') {
        $url = $this->url . $type . '/?auth_key=' . $this->generateAuthKey();
        $r = $this->rest->get($url);
        return $this->processResponse($r);
    }

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

    public function getLightboxes() {
        return $this->getCollections('lightbox');
    }

    public function getLightbox($id, $params=null) {
        return $this->getCollection('lightbox', $id, $params);
    }

    public function getClipboxes() {
        return $this->getCollections('video');
    }

    public function getClipbox($id, $params=null) {
        return $this->getCollection('clipbox', $id, $params);
    }

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

    public function getDownloadUrl($download_id) {
        $params = array(
            'auth_key'=>$this->generateAuthKey($download_id),
            'download_id'=>$download_id
        );
        $url = $this->url . 'download?' . http_build_query($params);
        return $url;
   }

    public function download($download_id) {
        $url = $this->getDownloadUrl($download_id);
        $r = $this->rest->get($url);
        return $this->processResponse($r);
    }

    public function generateAuthKey($id=null) {
        if ( is_null($id) ) {
            $auth_key = sha1($this->secret_key . $this->account_id);
        } else {
            $auth_key = sha1($this->secret_key . $this->account_id . $id);
        }
        return $auth_key;
    }

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