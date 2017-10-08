<?php
/**
 * Created by PhpStorm.
 * User: Giansalex
 * Date: 15/07/2017
 * Time: 23:13
 */

namespace Greenter\Ws\Services;

use Greenter\Model\Response\Error;
use Greenter\Ws\Reader\DomCdrReader;
use Greenter\Ws\Reader\XmlErrorReader;
use Greenter\Zip\ZipFactory;

/**
 * Class BaseSunat
 * @package Greenter\Ws\Services
 */
class BaseSunat
{
    /**
     * @var WsClientInterface
     */
    private $client;

    /**
     * @return WsClientInterface
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param WsClientInterface $client
     * @return BaseSunat
     */
    public function setClient($client)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * Get error from Fault Exception.
     *
     * @param \SoapFault $fault
     * @return Error
     */
    protected function getErrorFromFault(\SoapFault $fault)
    {
        $err = new Error();
        $err->setCode($fault->faultcode);
        $code = preg_replace('/[^0-9]+/', '', $err->getCode());
        $msg = '';

        if (empty($code)) {
            $code = preg_replace('/[^0-9]+/', '', $fault->faultstring);
        }

        if ($code) {
            $msg = $this->getMessageError($code);
            $err->setCode($code);
        }

        if (empty($msg)) {
            $msg = isset($fault->detail) ? $fault->detail->message : $fault->faultstring;
        }

        return $err->setMessage($msg);
    }

    /**
     * @param $zipContent
     * @return \Greenter\Model\Response\CdrResponse
     */
    protected function extractResponse($zipContent)
    {
        $zip = new ZipFactory();
        $xml = $zip->decompressLastFile($zipContent);
        $reader = new DomCdrReader();

        return $reader->getCdrResponse($xml);
    }

    /**
     * @param $code
     * @return string
     */
    protected function getMessageError($code)
    {
        $search = new XmlErrorReader();
        $msg = $search->getMessageByCode(intval($code));

        return $msg;
    }
}