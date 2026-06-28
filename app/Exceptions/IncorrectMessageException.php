<?php

namespace App\Exceptions;

class IncorrectMessageException extends \Exception
{
    protected bool $clearConversation;

    /**
     * Creates new instance of IncorrectMessageException
     *
     * @param  string  $replyMessage  What bot will reply to user when exception is thrown
     * @param  bool  $clearConversation  Determines will bot clear user's conversation when exception is thrown
     * @param int code
     * @return IncorrectMessageException
     */
    public function __construct(string $replyMessage = '', bool $clearConversation = false, int $code = 0, ?\Throwable $previous = null)
    {
        $this->clearConversation = $clearConversation;

        return parent::__construct($replyMessage, $code, $previous);
    }

    public function shouldClearConversation()
    {
        return $this->clearConversation;
    }
}
