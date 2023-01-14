<?php

namespace Tarekdj\DockerClient\Encoding;

/**
 * Transform a regular stream into a chunked one.
 *
 * @author Joel Wurtz <joel.wurtz@gmail.com>
 */
class ChunkStream extends FilteredStream
{
    /**
     * {@inheritdoc}
     */
    protected function readFilter(): string
    {
        return 'chunk';
    }

    /**
     * {@inheritdoc}
     */
    protected function writeFilter(): string
    {
        return 'dechunk';
    }

    /**
     * {@inheritdoc}
     */
    protected function fill(): void
    {
        parent::fill();

        if ($this->stream->eof()) {
            $this->buffer .= "0\r\n\r\n";
        }
    }
}
