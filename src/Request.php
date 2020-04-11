<?php
/**
 * Created by PhpStorm.
 * User: cronfy
 * Date: 11.03.17
 * Time: 13:20
 */

namespace cronfy\canonical;
use Yii;

class Request
{
    protected
        $canonical_scheme,
        $canonical_host
    ;

    public function getHost() {
        if (!$host = @$_SERVER['HTTP_HOST'] ?: @$_SERVER['SERVER_NAME']) {
            throw new \Exception("Failed to detect host");
        }

        return $host;
    }

    public function getCanonicalHost() {
        return $this->canonical_host ?: $this->getHost();
    }

    /**
     * @param string $canonical_host
     */
    public function setCanonicalHost($canonical_host)
    {
        $this->canonical_host = $canonical_host;
    }

    /**
     * @return \yii\web\Request
     */
    public function getRequest() {
        if (Yii::$app) {
            return Yii::$app->request;
        }

        return new \yii\web\Request();
    }

    public function warnEscapeFromHttps() {
        // необходимо в <head> указать следующий тег:
        //
        // <meta name="referrer" content="origin-when-cross-origin"/>
        //
        // Он означает, что при переходе по ссылке с https на http БУДЕТ
        // передаваться referrer в виде schema://host(:port)/
        // Если этот тег не указать, то при переходе на http не будет
        // передаваться referrer, и мы не узнаем, с какой страницы произошел такой переход.

        $request = $this->getRequest();
        $canonical_host = $this->getCanonicalHost();

        if ($referrer = $request->referrer) {
            if (
                // referrer - SSL на нашем сайте
                preg_match('#https://' . $canonical_host. '#', $referrer)
                // а сейчас не SSL
                && !$request->isSecureConnection
            ) {
                Yii::warning(
                    'Connection was forwarded to not secure: '
                    . $referrer
                    . ' => '
                    . $request->hostInfo . $_SERVER['REQUEST_URI']
                    ,
                    'app/ssl'
                );
            }
        }
    }

    public function getScheme() {
        $request = $this->getRequest();
        return $request->isSecureConnection ? 'https' : 'http';
    }

    /**
     * @return string
     */
    public function getCanonicalScheme()
    {
        return $this->canonical_scheme ?: $this->getScheme();
    }

    /**
     * @param string $canonical_scheme
     */
    public function setCanonicalScheme($canonical_scheme)
    {
        $this->canonical_scheme = $canonical_scheme;
    }

    protected $allowedHostsList;
    /**
     * @param $hosts [['scheme' => ..., 'host' => ..., 'aliases' => [...], 'default' => true|false|undefined ], ... ]
     */
    public function setAllowedHostsList($hosts) {
        $this->allowedHostsList = $hosts;
    }

    public function redirectToCanonical() {
        $request = $this->getRequest();

        $canonical_scheme = null;
        $canonical_host = null;

        $currentHost = $this->getHost();

        $defaultHost = null;

        foreach (($this->allowedHostsList ?: []) as $item) {
            if (!$defaultHost) {
                if (@$item['default']) {
                    $defaultHost = $item;
                }
            }

            if ($canonical_host) {
                if ($defaultHost) {
                    // everything resolved
                    break;
                }

                continue; // waiting for $defaultHost
            }

            $itemAiases = @$item['aliases'] ?: [];

            if (($item['host'] == $currentHost) || in_array($currentHost, $itemAiases)) {
                $canonical_host = $item['host'];
                $canonical_scheme = $item['scheme'];
                break;
            }
        }

        if (!$canonical_host) {
            if ($defaultHost) {
                $canonical_host = $defaultHost['host'];
                $canonical_scheme = $defaultHost['scheme'];
            } else {
                $canonical_scheme = $this->getCanonicalScheme();
                $canonical_host = $this->getCanonicalHost();
            }
        }

        // redirect to https
        if ($request->isGet) {
            if ($this->getScheme() != $canonical_scheme || $this->getHost() != $canonical_host) {
                $url = $canonical_scheme . '://' . $canonical_host . $_SERVER['REQUEST_URI'];
                header("HTTP/1.1 301 Moved Permanently");
                header("Location: $url");
                exit();
            }
        }
    }


}
