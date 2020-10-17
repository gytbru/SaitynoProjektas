<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();
        $response = new JsonResponse();

        if ($exception instanceof HttpExceptionInterface) {

            $response->setStatusCode($exception->getStatusCode());
            $response->setData(
                [
                    'status' => $exception->getStatusCode(),
                    'errors' => $exception->getMessage(),
                ]
            );
        } else {
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            $response->setData(
                [
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'errors' => 'Internal server error',
                ]
            );
        }
        $event->setResponse($response);
    }
}