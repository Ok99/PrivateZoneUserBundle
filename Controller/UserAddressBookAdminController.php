<?php

namespace Ok99\PrivateZoneCore\UserBundle\Controller;

use Ok99\PrivateZoneBundle\Controller\CRUDController;
use Ok99\PrivateZoneBundle\Tools\RequestTool;
use Ok99\PrivateZoneCore\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;

class UserAddressBookAdminController extends CRUDController
{

    /**
     * @param User $object
     */
    protected function preShow(Request $request, $object)
    {
        $address = $object->getAddress();
        $addressChecksum = hash('sha256', $address);

        if (
            $object->getAddressChecksum() === null ||
            $object->getAddressChecksum() !== $addressChecksum ||
            $object->getAddressLatitude() === null ||
            $object->getAddressLongitude() === null
        ) {
            $searchAddressChunks = [];
            if ($object->getStreet()) {
                $searchAddressChunks[] = $object->getStreet();
            }
            if ($object->getCity()) {
                $searchAddressChunks[] = rtrim($object->getCity(), '0123456789 ');
            }

            $searchAddress = implode(' ', $searchAddressChunks);

            $searchData = RequestTool::fetchJsonData('https://nominatim.openstreetmap.org/search?format=json&q=' . urlencode($searchAddress));

            if (
                $searchData === null ||
                !is_array($searchData) ||
                !is_object($searchData[0]) ||
                !$searchData[0]->lat ||
                !$searchData[0]->lon
            ) {
                return;
            }

            $object->setAddressChecksum($addressChecksum);
            $object->setAddressLatitude($searchData[0]->lat);
            $object->setAddressLongitude($searchData[0]->lon);

            $this->getDoctrine()->getManager()->flush($object);
        }
    }

}