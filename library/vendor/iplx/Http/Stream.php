<?php

namespace iplx\Http;

use Exception;
use InvalidArgumentException;
use RuntimeException;
use Psr\Http\Message\StreamInterface;

class Stream implements StreamInterface
{
    protected $stream;

    protected $size;

    protected $seekable;

    protected $readable;

    protected $writable;

    public function __construct($stream)
    {
        if (! is_resource($stream)) {
            throw new InvalidArgumentException();
        }

        $this->stream = $stream;

        $meta = stream_get_meta_data($this->stream);
        $this->seekable = $meta['seekable'];
        $this->readable = preg_match('/[r+]/', $meta['mode']) === 1;
        $this->writable = preg_match('/[waxc+]/', $meta['mode']) === 1;
    }

    public function __destruct()
    {
        $this->close();
    }

    public function __toString()
    {
        try {
            $this->seek(0);
            $contents = stream_get_contents($this->stream);
        } catch (Exception $e) {
            $contents = '';
        }

        return $contents;
    }

    public function close()
    {
        if (isset($this->stream)) {
            if (is_resource($this->stream)) {
                fclose($this->stream);
            }
            $this->detach();
        }
    }

    public function detach()
    {
        if (! isset($this->stream)) {
            return null;
        }

        $stream = $this->stream;

        $this->stream = null;
        $this->size = null;
        $this->seekable = false;
        $this->readable = false;
        $this->writable = false;

        return $stream;
    }

    public function getSize()
    {
        if ($this->size !== null) {
            return $this->size;
        }

        if (! isset($this->stream)) {
            return null;
        }

        $stats = fstat($this->stream);
        $this->size = $stats['size'];

        return $this->size;
    }

    public function tell()
    {
        $this->assertAttached();

        $position = ftell($this->stream);

        if ($position === false) {
            throw new RuntimeException();
        }

        return $position;
    }

    public function eof()
    {
        $this->assertAttached();

        return feof($this->stream);
    }

    public function isSeekable()
    {
        return $this->seekable;
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        $this->assertSeekable();

        if (fseek($this->stream, $offset, $whence) === -1) {
            throw new RuntimeException();
        }
    }

    public function rewind()
    {
        $this->seek(0);
    }

    public function isWritable()
    {
        return $this->writable;
    }

    public function write($string)
    {
        $this->assertWritable();

        $written = fwrite($this->stream, $string);

        if ($written === false) {
            throw new RuntimeException();
        }

        return $written;
    }

    public function isReadable()
    {
        return $this->readable;
    }

    public function read($length)
    {
        $this->assertReadable();

        $data = fread($this->stream, $length);

        if ($data === false) {
            throw new RuntimeException();
        }

        return $data;
    }

    public function getContents()
    {
        $this->assertReadable();

        $contents = stream_get_contents($this->stream);

        if ($contents === false) {
            throw new RuntimeException();
        }

        return $contents;
    }

    public function getMetadata($key = null)
    {
        // TODO: Implement getMetadata() method.
    }

    public function assertAttached()
    {
        if (! isset($this->stream)) {
            throw new RuntimeException();
        }
    }

    public function assertSeekable()
    {
        $this->assertAttached();

        if (! $this->isSeekable()) {
            throw new RuntimeException();
        }
    }

    public function assertReadable()
    {
        $this->assertAttached();

        if (! $this->isReadable()) {
            throw new RuntimeException();
        }
    }

    public function assertWritable()
    {
        $this->assertAttached();

        if (! $this->isWritable()) {
            throw new RuntimeException();
        }
    }

    public static function open($filename = 'php://temp', $mode = 'r+')
    {
        $stream = fopen($filename, $mode);

        return new static($stream);
    }

    public static function create($resource = null)
    {
        if (is_scalar($resource)) {
            $stream = fopen('php://temp', 'r+');

            if ($resource !== '') {
                fwrite($stream, $resource);
                fseek($stream, 0);
            }

            return new static($stream);
        }

        if (is_resource($resource)) {
            return new static($resource);
        }

        return static::open();
    }

}
