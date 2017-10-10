<?php
/**
 * Created by PhpStorm.
 * User: Administrador
 * Date: 09/08/2017
 * Time: 01:40 PM
 */

namespace Tests\Greenter\Xml\Builder;

use Greenter\Model\Despatch\Despatch;
use Greenter\Model\DocumentInterface;
use Greenter\Model\Perception\Perception;
use Greenter\Model\Retention\Retention;
use Greenter\Model\Voided\Reversion;
use Greenter\Validator\SymfonyValidator;
use Greenter\Xml\Builder\BuilderInterface;
use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Xml\Builder\DespatchBuilder;
use Greenter\Xml\Builder\PerceptionBuilder;
use Greenter\Xml\Builder\RetentionBuilder;
use Greenter\Xml\Builder\VoidedBuilder;

/**
 * Trait CeBuilderTrait
 * @package Tests\Greenter\Xml\Builder
 */
trait CeBuilderTrait
{
    /**
     * @param string $className
     * @return BuilderInterface
     */
    private function getGenerator($className)
    {
        $builders = [
            Despatch::class => DespatchBuilder::class,
            Perception::class => PerceptionBuilder::class,
            Retention::class => RetentionBuilder::class,
            Reversion::class => VoidedBuilder::class,
        ];
        $builder = new $builders[$className]();

        return $builder;
    }

    /**
     * @param DocumentInterface $document
     * @return string
     */
    private function build(DocumentInterface $document)
    {
        $generator = $this->getGenerator(get_class($document));

        return $generator->build($document);
    }

    /**
     * @return Company
     */
    private function getCompany()
    {
        $company = new Company();
        $address = new Address();
        $address->setUbigueo('150101')
            ->setDepartamento('LIMA')
            ->setProvincia('LIMA')
            ->setDistrito('LIMA')
            ->setDireccion('AV GS');
        $company->setRuc('20000000001')
            ->setRazonSocial('EMPRESA SAC')
            ->setNombreComercial('EMPRESA')
            ->setAddress($address);

        return $company;
    }

    /**
     * @return SymfonyValidator
     */
    private function getValidator()
    {
        $validator = new SymfonyValidator();

        return $validator;
    }
}