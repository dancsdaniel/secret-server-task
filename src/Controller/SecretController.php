<?php

namespace App\Controller;

use App\Entity\Secret;
use App\Repository\SecretRepository;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;

class SecretController extends AbstractController
{
    public function __construct(RequestStack $rs)
    {
        $encoders = [
            new XmlEncoder([
                XmlEncoder::ROOT_NODE_NAME => 'Secret',
                XmlEncoder::ENCODING => 'UTF-8',
            ]),
            new JsonEncoder()
        ];
        $normalizers = [new DateTimeNormalizer(
            [DateTimeNormalizer::FORMAT_KEY => 'Y-m-d\TH:i:s.v\Z']
        ), new ObjectNormalizer()];
        $this->serializer = new Serializer($normalizers, $encoders);

        $this->request = $rs->getCurrentRequest();
        $this->acceptHeader = current($this->request->getAcceptableContentTypes());
        $this->responseFormat = $this->request->getFormat($this->acceptHeader);
    }

    #[Route('/api/secret', methods: ['POST'])]
    public function postSecret(EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        $secret = new Secret();

        $constraints = new Assert\Collection([
            'secret' => [
                new Assert\NotBlank()
            ],
            'expireAfterViews' => [
                new Assert\NotBlank(),
                new Assert\Type('numeric'),
                new Assert\GreaterThan(0)
            ],
            'expireAfter' => [
                new Assert\NotBlank(),
                new Assert\Type('numeric'),
                new Assert\GreaterThan(0)
            ]
        ]);
        $validationErrors = $validator->validate($this->request->request->all(), $constraints);

        if(count($validationErrors)!=0){
            $statusCode = 405;
            $responseBody = 'Invalid input';
            
        }
        else{
            $secret->setSecretText($this->request->request->get('secret'));
            $secret->setRemainingViews($this->request->request->get('expireAfterViews'));
            $secret->setExpiresAt($this->request->request->get('expireAfter'));

            $entityManager->persist($secret);
            $entityManager->flush();

            $statusCode = 200;
            $responseBody = $this->serializer->serialize($secret, $this->responseFormat, [AbstractNormalizer::IGNORED_ATTRIBUTES => ['id']]);
        }

        return new Response($responseBody, $statusCode, array(
            'Content-Type' => $this->acceptHeader
        ));
    }

    #[Route('/api/secret/{hash}', methods: ['GET'])]
    public function getSecret(SecretRepository $secretRepository, string $hash): Response
    {
        $secret = $secretRepository->findValidHash($hash);

        if (!$secret) {
            $statusCode = 404;
            $responseBody = 'Secret not found';
        }
        else {
            $secret->minusRemainingViews();
            $secretRepository->save($secret, true);

            $statusCode = 200;
            $responseBody = $this->serializer->serialize($secret, $this->responseFormat, [AbstractNormalizer::IGNORED_ATTRIBUTES => ['id']]);
        }

        return new Response($responseBody, $statusCode, array(
            'Content-Type' => $this->acceptHeader
        ));
    }
}
