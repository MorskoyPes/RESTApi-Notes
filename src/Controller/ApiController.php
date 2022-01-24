<?php

namespace App\Controller;

use App\Entity\Masters;
use App\Entity\Notes;
use App\Repository\NotesRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\MastersRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ApiController extends AbstractController
{
    private $applyTime  = [36000, 39600, 43200, 46800, 50400, 54000, 57600, 61200, 64800, 72000, 75600];
    private $format     = 'H:i:s';
    private $statusCode = 200;

    /**
     * @var MastersRepository
     */
    private $mastersRepository;

    /**
     * @var NotesRepository
     */
    private $notesRepository;

    /**
     * @var EntityManagerInterface
     */
    private $em;


    public function __construct(ManagerRegistry $doctrine, EntityManagerInterface $entityManager)
    {
        $this->em                = $entityManager;
        $this->mastersRepository = $this->em->getRepository(Masters::class);
        $this->notesRepository   = $this->em->getRepository(Notes::class);
        if (!$doctrine->getManager()) {
            throw new \Exception("Connection database is lost");
        }
    }

    /**
     * @Route("/get-masters", methods={"GET"})
     */
    public function getMasters(): JsonResponse
    {
        try {
            $masters = $this->mastersRepository->findAll();
            if (!$masters) {
                $this->statusCode = Response::HTTP_NOT_FOUND;
                throw new \Exception('Masters do not exist');
            }
            foreach ($masters as $master) {
                $response[] = [
                    'id'       => $master->getId(),
                    'Name'     => $master->getName(),
                    'Lastname' => $master->getLastname()
                ];
            }
        } catch (\Exception $e) {
            $response = [$e->getMessage(), $this->statusCode];
        }

        return new JsonResponse($response, $this->statusCode, ["Content-Type" => "application/json"]);
    }

    /**
     * @Route("/add-reserved", methods={"POST", "GET"})
     */
    public function addReserved(Request $request): JsonResponse
    {
        try {
            if ($request->query) {
                $time     = $request->query->get('time');
                $date     = $request->query->get('date');
                $master   = $request->query->get('master');
                $clientId = $request->query->get('clientid');

                if ($checkMaster = $this->checkMaster($master)) {
                    if ($time !== NULL && $date !== NULL) {
                        $timeStart = (new \DateTime())->setTimestamp('36000')->getTimestamp();
                        $timeEnd   = (new \DateTime())->setTimestamp('36000')->modify('+11 hours')->getTimestamp();
                        if (!strripos($time, ':')) {
                            $time = $time . ":00:00";
                        }
                        $timeReserve = (new \DateTime('1970-01-01 ' . $time))->getTimestamp();

                        if ($timeStart <= $timeReserve && $timeEnd >= $timeReserve) {
                            if (in_array($timeReserve, $this->applyTime)) {
                                $note = $this->notesRepository->findOneBy([
                                    'master' => $master,
                                    'time'   => \DateTime::createFromFormat($this->format, date($this->format, $timeReserve)),
                                    'date'   => \DateTime::createFromFormat('Y-m-d', $date)
                                ]);
                                if ($note) {
                                    $this->statusCode = Response::HTTP_NOT_FOUND;
                                    throw new \Exception('This time was already booked');
                                } else {

                                    $note = (new Notes())
                                        ->setTime(\DateTime::createFromFormat($this->format, date($this->format, $timeReserve)))
                                        ->setDate(\DateTime::createFromFormat('Y-m-d', $date))
                                        ->setMaster($checkMaster)
                                        ->setClientId($clientId);

                                    $this->em->persist($note);
                                    $this->em->flush();

                                    $response = 'Success';
                                }
                            } else {
                                $this->statusCode = Response::HTTP_BAD_REQUEST;
                                throw new \Exception('Time format must be round h:m:s 00:00:00 OR simple integer');
                            }
                        } else {
                            $this->statusCode = Response::HTTP_BAD_REQUEST;
                            throw new \Exception('Time must be from 10 to 21 PM');
                        }
                    } else {
                        $this->statusCode = Response::HTTP_BAD_REQUEST;
                        throw new \Exception('Time and date don`t be empty');
                    }
                }
            } else {
                $this->statusCode = Response::HTTP_BAD_REQUEST;
                throw new \Exception('Params is empty');
            }
        } catch (\Exception $e) {
            $response = [$e->getMessage(), $this->statusCode];
        }

        return new JsonResponse($response, $this->statusCode, ["Content-Type" => "application/json"]);
    }

    /**
     * @Route("/get-free", methods={"GET"})
     */
    public function getFree(Request $request): JsonResponse
    {
        try {
            if ($request->query) {
                $date   = $request->query->get('date');
                $master = $request->query->get('master');

                if ($this->checkMaster($master)) {
                    if ($date !== NULL && \DateTime::createFromFormat('Y-m-d', $date)) {
                        $notes = $this->notesRepository->findBy([
                            'master' => $master,
                            'date'   => \DateTime::createFromFormat('Y-m-d', $date)
                        ], ['time' => 'ASC']);

                        if (!$notes) {
                            $this->statusCode = Response::HTTP_NOT_FOUND;
                            throw new \Exception('Reserve is closed');
                        }
                        foreach ($notes as $note) {
                            $reservedTime[] = $note->getTime()->getTimestamp();
                        }
                        $freeReserve = array_diff($this->applyTime, $reservedTime);
                        if (!$freeReserve) {
                            $this->statusCode = Response::HTTP_NOT_FOUND;
                            throw new \Exception('Reserve is closed');
                        }
                        foreach ($freeReserve as $item) {
                            $response[] = (date('H:i:s', $item));
                        }
                    } else {
                        $this->statusCode = Response::HTTP_BAD_REQUEST;
                        throw new \Exception('Date is empty OR invalid format (Y-m-d)');
                    }
                }
            }
        } catch (\Exception $e) {
            $response = [$e->getMessage(), $this->statusCode];
        }

        return new JsonResponse($response, $this->statusCode, ["Content-Type" => "application/json"]);
    }

    /**
     * @Route("/get-reserved", methods={"GET"})
     */
    public function getReserved(): JsonResponse
    {
        try {
            $notes = $this->notesRepository->findBy([], ['master' => 'ASC']);
            if (!$notes) {
                $this->statusCode = Response::HTTP_NOT_FOUND;
                throw new \Exception('Notes is not found');
            }
            foreach ($notes as $note) {
                $response[] = [
                    'id'       => $note->getMaster()->getId(),
                    'Name'     => $note->getMaster()->getName(),
                    'Lastname' => $note->getMaster()->getLastname(),
                    'Time'     => $note->getTime(),
                    'Date'     => $note->getDate(),
                ];
            }
        } catch (\Exception $e) {
            $response = [$e->getMessage(), $this->statusCode];
        }

        return new JsonResponse($response, $this->statusCode, ["Content-Type" => "application/json"]);
    }

    public function checkMaster($master): Masters
    {
        if ($master) {
            $masters = $this->mastersRepository->findOneBy([
                'id' => $master,
            ]);
            if (!$masters) {
                $this->statusCode = Response::HTTP_NOT_FOUND;
                throw new \Exception('Master id is not found');
            }
        } else {
            $this->statusCode = Response::HTTP_NOT_FOUND;
            throw new \Exception('Master id is empty');
        }
        return $masters;
    }
}
