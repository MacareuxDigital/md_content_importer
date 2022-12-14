<?php

namespace Macareux\ContentImporter\Http;

use Concrete\Core\Error\ErrorList\ErrorList;

class PreviewResponse implements \JsonSerializable
{
    protected $error;

    protected $response = '';

    public function __construct()
    {
        $this->error = new ErrorList();
    }

    /**
     * @return ErrorList
     */
    public function getError(): ErrorList
    {
        return $this->error;
    }

    /**
     * @return string
     */
    public function getResponse(): string
    {
        return $this->response;
    }

    /**
     * @param string $response
     */
    public function setResponse(string $response): void
    {
        $this->response = $response;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        if ($this->getError()->has()) {
            return $this->getError()->jsonSerialize();
        }
        $o = new \stdClass();
        $o->error = false;
        $o->response = $this->getResponse();

        return $o;
    }
}
