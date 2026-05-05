<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Kernel;
use App\Entity\InsurancePackage;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/../.env');

$kernel = new Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

$packages = [
    // Cars (Vehicle)
    [
        'name' => 'Basic Auto Shield',
        'assetType' => 'Vehicle',
        'description' => 'Essential third-party liability coverage for budget-conscious drivers.',
        'coverage' => 'Third-party liability, basic towing assistance.',
        'price' => '250.00',
        'risk' => '1.10',
        'months' => 12
    ],
    [
        'name' => 'Standard Vehicle Plus',
        'assetType' => 'Vehicle',
        'description' => 'Comprehensive coverage including theft and fire protection.',
        'coverage' => 'Theft, Fire, Collision, Roadside assistance, Replacement car.',
        'price' => '650.00',
        'risk' => '1.00',
        'months' => 12
    ],
    [
        'name' => 'Premium Road Protector',
        'assetType' => 'Vehicle',
        'description' => 'The ultimate protection for high-end vehicles with full risk coverage.',
        'coverage' => 'Full risk (All hazards), Glass breakage, Natural disasters, Zero deductible option.',
        'price' => '1200.00',
        'risk' => '0.90',
        'months' => 12
    ],
    // Homes (Property)
    [
        'name' => 'Essential Home Guard',
        'assetType' => 'Property',
        'description' => 'Basic protection for your apartment or small house against fire and water damage.',
        'coverage' => 'Fire, Water damage, Explosion, Civil liability.',
        'price' => '180.00',
        'risk' => '1.05',
        'months' => 12
    ],
    [
        'name' => 'Family Nest Secure',
        'assetType' => 'Property',
        'description' => 'Reliable coverage for family homes including theft and furniture protection.',
        'coverage' => 'Burglary, Electrical damage, Storm & Hail, Garden furniture coverage.',
        'price' => '450.00',
        'risk' => '1.00',
        'months' => 12
    ],
    [
        'name' => 'Estate Luxury Haven',
        'assetType' => 'Property',
        'description' => 'High-limit coverage for luxury estates, valuable art collections, and jewelry.',
        'coverage' => 'Art & Jewelry, Legal protection, Pool & Outbuildings, Replacement value at 100%.',
        'price' => '1500.00',
        'risk' => '0.85',
        'months' => 12
    ],
];

foreach ($packages as $p) {
    $package = new InsurancePackage();
    $package->setName($p['name']);
    $package->setAssetType($p['assetType']);
    $package->setDescription($p['description']);
    $package->setCoverageDetails($p['coverage']);
    $package->setBasePrice($p['price']);
    $package->setRiskMultiplier($p['risk']);
    $package->setDurationMonths($p['months']);
    $package->setIsActive(true);

    $em->persist($package);
}

$em->flush();

echo "Successfully seeded " . count($packages) . " insurance packages.\n";
