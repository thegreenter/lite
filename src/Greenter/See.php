<?php
/**
 * Created by PhpStorm.
 * User: Administrador
 * Date: 16/10/2017
 * Time: 04:23 PM.
 */

declare(strict_types=1);

namespace Greenter;

use DOMDocument;
use Exception;
use Greenter\Builder\BuilderInterface;
use Greenter\Factory\FeFactory;
use Greenter\Model\DocumentInterface;
use Greenter\Model\Summary\Summary;
use Greenter\Model\Voided\Reversion;
use Greenter\Model\Voided\Voided;
use Greenter\Services\SenderInterface;
use Greenter\Validator\ErrorCodeProviderInterface;
use Greenter\Ws\Reader\XmlFilenameExtractor;
use Greenter\Ws\Reader\XmlReader;
use Greenter\Ws\Resolver\XmlTypeResolver;
use Greenter\Ws\Services\BillSender;
use Greenter\Ws\Services\ExtService;
use Greenter\Ws\Services\SoapClient;
use Greenter\Ws\Services\SummarySender;
use Greenter\XMLSecLibs\Sunat\SignedXml;

/**
 * Sistema de Emision del Contribuyente.
 *
 * Class See
 */
class See
{
    /**
     * @var FeFactory
     */
    private $factory;

    /**
     * @var SoapClient
     */
    private $wsClient;

    /**
     * @var array
     */
    private $builders;

    /**
     * @var array
     */
    private $summarys;

    /**
     * @var SignedXml
     */
    private $signer;

    /**
     * @var ErrorCodeProviderInterface|null
     */
    private $codeProvider;

    /**
     * Twig Render Options.
     *
     * @var array
     */
    private $options = ['autoescape' => false];

    /**
     * See constructor.
     */
    public function __construct()
    {
        $this->factory = new FeFactory();
        $this->wsClient = new SoapClient();
        $this->signer = new SignedXml();
        $this->builders = [
            Model\Sale\Invoice::class => Xml\Builder\InvoiceBuilder::class,
            Model\Sale\Note::class => Xml\Builder\NoteBuilder::class,
            Model\Summary\Summary::class => Xml\Builder\SummaryBuilder::class,
            Model\Voided\Voided::class => Xml\Builder\VoidedBuilder::class,
            Model\Despatch\Despatch::class => Xml\Builder\DespatchBuilder::class,
            Model\Retention\Retention::class => Xml\Builder\RetentionBuilder::class,
            Model\Perception\Perception::class => Xml\Builder\PerceptionBuilder::class,
            Model\Voided\Reversion::class => Xml\Builder\VoidedBuilder::class,
        ];
        $this->summarys = [Summary::class, Summary::class, Voided::class, Reversion::class];
        $this->factory->setSigner($this->signer);
    }

    /**
     * Set Xml Builder Options.
     *
     * @param array $options
     */
    public function setBuilderOptions(array $options)
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * @param string $directory
     */
    public function setCachePath(string $directory)
    {
        $this->options['cache'] = $directory;
    }

    /**
     * @param string $certificate
     */
    public function setCertificate(string $certificate)
    {
        $this->signer->setCertificate($certificate);
    }

    /**
     * @param string $user
     * @param string $password
     */
    public function setCredentials(string $user, string $password)
    {
        $this->wsClient->setCredentials($user, $password);
    }

    /**
     * @param string $service
     */
    public function setService(?string $service)
    {
        $this->wsClient->setService($service);
    }

    /**
     * Set error code provider.
     *
     * @param ErrorCodeProviderInterface $codeProvider
     */
    public function setCodeProvider(?ErrorCodeProviderInterface $codeProvider)
    {
        $this->codeProvider = $codeProvider;
    }

    /**
     * Get signed xml from document.
     *
     * @param DocumentInterface $document
     *
     * @return string
     */
    public function getXmlSigned(DocumentInterface $document)
    {
        $classDoc = get_class($document);

        return $this->factory
            ->setBuilder($this->getBuilder($classDoc))
            ->getXmlSigned($document);
    }

    /**
     * Envia documento.
     *
     * @param DocumentInterface $document
     *
     * @return Model\Response\BaseResult
     */
    public function send(DocumentInterface $document)
    {
        $classDoc = get_class($document);
        $this->factory
            ->setBuilder($this->getBuilder($classDoc))
            ->setSender($this->getSender($classDoc));

        return $this->factory->send($document);
    }

    /**
     * Envia xml generado.
     *
     * @param string $type Document Type
     * @param string $name Xml Name
     * @param string $xml  Xml Content
     *
     * @return Model\Response\BaseResult
     */
    public function sendXml(string $type, string $name, string $xml)
    {
        $this->factory
            ->setBuilder($this->getBuilder($type))
            ->setSender($this->getSender($type));

        return $this->factory->sendXml($name, $xml);
    }

    /**
     * Envia XML generado previamente.
     *
     * @param string $xmlContent
     *
     * @return Model\Response\BaseResult
     *
     * @throws Exception
     */
    public function sendXmlFile(string $xmlContent)
    {
        $doc = new DOMDocument();
        $doc->loadXML($xmlContent);

        $reader = new XmlReader();
        $resolver = new XmlTypeResolver($reader);
        $type = $resolver->getType($doc);

        $extractor = new XmlFilenameExtractor($reader);
        $name = $extractor->getFilename($doc);

        return $this->sendXml($type, $name, $xmlContent);
    }

    /**
     * @param string $ticket
     *
     * @return Model\Response\StatusResult
     */
    public function getStatus(string $ticket)
    {
        $sender = new ExtService();
        $sender->setClient($this->wsClient);

        return $sender->getStatus($ticket);
    }

    /**
     * @return FeFactory
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * @param string $class
     *
     * @return BuilderInterface
     */
    private function getBuilder(string $class)
    {
        $builder = $this->builders[$class];

        return new $builder($this->options);
    }

    /**
     * @param string $class
     *
     * @return SenderInterface
     */
    private function getSender(string $class)
    {
        $sender = in_array($class, $this->summarys) ? new SummarySender() : new BillSender();
        $sender->setClient($this->wsClient);
        if ($this->codeProvider) {
            $sender->setCodeProvider($this->codeProvider);
        }

        return $sender;
    }
}
