<?php

namespace Tarekdj\DockerClient\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Tarekdj\DockerClient\Exception\StreamException;
use Tarekdj\DockerClient\Exception\TimeoutException;

/**
 * Stream implementation for Socket Client.
 *
 * This implementation is used to have a Stream which react better to the Socket Client behavior.
 *
 * The main advantage is you can get the response of a request even if it's not finish, the response is available
 * as soon as all headers are received, this stream will have the remaining socket used for the request / response
 * call.
 *
 * It is only readable once, if you want to read the content multiple times, you can store contents of this
 * stream into a variable or encapsulate it in a buffered stream.
 *
 * Writing and seeking is disable to avoid weird behaviors.
 *
 * @author Joel Wurtz <joel.wurtz@gmail.com>
 */
class Stream implements StreamInterface, \Stringable
{
    /**
     * @var bool Is stream detached
     */
    private bool $isDetached = false;

    /**
     * @var int<0, max> Size of the stream readed, to avoid reading more than available and have the user blocked
     */
    private int $readed = 0;

    /**
     * Create the stream.
     *
     * @param resource         $socket
     * @param int<0, max>|null $size
     */
    public function __construct(
        /**
         * @var RequestInterface request associated to this stream
         */
        private RequestInterface $request,
        private $socket,
        private ?int $size = null
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        try {
            return $this->getContents();
        } catch (\Exception) {
            return '';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        if ($this->isDetached || null === $this->socket) {
            throw new StreamException('Stream is detached');
        }
        fclose($this->socket);
    }

    /**
     * {@inheritdoc}
     */
    public function detach()
    {
        if ($this->isDetached) {
            return null;
        }
        $this->isDetached = true;
        $socket = $this->socket;
        $this->socket = null;

        return $socket;
    }

    /**
     * {@inheritdoc}
     *
     * @return int<0, max>|null
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * {@inheritdoc}
     */
    public function tell()
    {
        if ($this->isDetached || null === $this->socket) {
            throw new StreamException('Stream is detached');
        }
        $tell = ftell($this->socket);
        if (false === $tell) {
            throw new StreamException('ftell returned false');
        }

        return $tell;
    }

    /**
     * {@inheritdoc}
     */
    public function eof(): bool
    {
        if ($this->isDetached || null === $this->socket) {
            throw new StreamException('Stream is detached');
        }

        return feof($this->socket);
    }

    /**
     * {@inheritdoc}
     */
    public function isSeekable(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        throw new StreamException('This stream is not seekable');
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function rewind()
    {
        throw new StreamException('This stream is not seekable');
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function write($string)
    {
        throw new StreamException('This stream is not writable');
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @param int<0, max> $length
     */
    public function read($length)
    {
        if ($this->isDetached || null === $this->socket) {
            throw new StreamException('Stream is detached');
        }
        if (null === $this->getSize()) {
            $read = fread($this->socket, $length);
            if (false === $read) {
                throw new StreamException('Failed to read from stream');
            }

            return $read;
        }

        if ($this->getSize() === $this->readed) {
            return '';
        }

        // Even if we request a length a non blocking stream can return less data than asked
        $read = fread($this->socket, $length);
        if (false === $read) {
            // PHP 8
            if ($this->getMetadata('timed_out')) {
                throw new TimeoutException('Stream timed out while reading data', $this->request);
            }
            throw new StreamException('Failed to read from stream');
        }

        // PHP 7: fread does not return false when timing out
        if ($this->getMetadata('timed_out')) {
            throw new TimeoutException('Stream timed out while reading data', $this->request);
        }

        $this->readed += strlen($read);

        return $read;
    }

    /**
     * {@inheritdoc}
     */
    public function getContents()
    {
        if ($this->isDetached || null === $this->socket) {
            throw new StreamException('Stream is detached');
        }

        if (null === $this->getSize()) {
            $contents = stream_get_contents($this->socket);
            if (false === $contents) {
                throw new StreamException('failed to get contents of stream');
            }

            return $contents;
        }

        $contents = '';

        $toread = $this->getSize() - $this->readed;
        while ($toread > 0) {
            $contents .= $this->read($toread);
            $toread = $this->getSize() - $this->readed;
        }

        return $contents;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($key = null)
    {
        if ($this->isDetached || null === $this->socket) {
            throw new StreamException('Stream is detached');
        }

        $meta = stream_get_meta_data($this->socket);

        if (null === $key) {
            return $meta;
        }

        return $meta[$key];
    }
}
