<?php

namespace Inspirum\Balikobot\Tests\Integration\Balikobot;

use Inspirum\Balikobot\Contracts\ExceptionInterface;
use Inspirum\Balikobot\Definitions\Country;
use Inspirum\Balikobot\Definitions\ServiceType;
use Inspirum\Balikobot\Definitions\Shipper;
use Inspirum\Balikobot\Model\Aggregates\OrderedPackageCollection;
use Inspirum\Balikobot\Model\Aggregates\PackageCollection;
use Inspirum\Balikobot\Model\Values\OrderedPackage;
use Inspirum\Balikobot\Model\Values\Package;

class DropPackagesTest extends AbstractBalikobotTestCase
{
    public function testValidRequest()
    {
        $service = $this->newBalikobot();

        $packages = new PackageCollection(Shipper::ZASILKOVNA);

        $package = new Package();
        $package->setServiceType(ServiceType::ZASILKOVNA_VMCZ);
        $package->setBranchId('12');
        $package->setRecName('Tomáš Novák');
        $package->setRecEmail('tets@test.cz');
        $package->setRecZip('12000');
        $package->setRecStreet('Ulice');
        $package->setRecCity('Praha');
        $package->setRecCountry(Country::CZECH_REPUBLIC);
        $package->setRecPhone('776555888');
        $package->setPrice(1000.00);
        $packages->add($package);

        $orderPackages = $service->addPackages($packages);

        $service->dropPackages($orderPackages);

        $this->assertTrue(true);
    }

    public function testInvalidRequest()
    {
        $service = $this->newBalikobot();

        $packages = new OrderedPackageCollection();

        $packages->add(new OrderedPackage('12345', Shipper::PPL, '0001', '1234'));
        $packages->add(new OrderedPackage('a-56789', Shipper::PPL, '0001', '5678'));

        try {
            $service->dropPackages($packages);
            $this->assertTrue(false, 'DROP request should thrown exception');
        } catch (ExceptionInterface $exception) {
            $this->assertEquals(400, $exception->getStatusCode());
            $this->assertTrue(isset($exception->getErrors()[0]['status']));
            $this->assertEquals(
                'Zásilka neexistuje, nebo již byla zpracována.',
                $exception->getErrors()[0]['status']
            );
            $this->assertTrue(isset($exception->getErrors()[1]['status']));
            $this->assertEquals(
                'Nedorazila žádná data ke zpracování nebo nemůžou být akceptována.',
                $exception->getErrors()[1]['status']
            );
        }
    }
}
