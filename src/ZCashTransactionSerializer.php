<?php

declare(strict_types=1);

namespace ZCash;

use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializer;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Parser;

class ZCashTransactionSerializer extends TransactionSerializer
{
    /**
     * @param Parser $parser
     * @return TransactionInterface
     */
    public function fromParser(Parser $parser): TransactionInterface
    {
        // https://raw.githubusercontent.com/zcash/zips/master/protocol/protocol.pdf

        $version = (int) $this->int32le->read($parser);

        $vin = [];
        $vinCount = $this->varint->read($parser);
        for ($i = 0; $i < $vinCount; $i++) {
            $vin[] = $this->inputSerializer->fromParser($parser);
        }

        $vout = [];
        $voutCount = $this->varint->read($parser);
        for ($i = 0; $i < $voutCount; $i++) {
            $vout[] = $this->outputSerializer->fromParser($parser);
        }

        // no flags found in specs.
//        $flags = 0;
//        if (count($vin) === 0) {
//            $flags = (int) $this->varint->read($parser);
//            if ($flags !== 0) {
//                $vinCount = $this->varint->read($parser);
//                for ($i = 0; $i < $vinCount; $i++) {
//                    $vin[] = $this->inputSerializer->fromParser($parser);
//                }
//
//                $voutCount = $this->varint->read($parser);
//                for ($i = 0; $i < $voutCount; $i++) {
//                    $vout[] = $this->outputSerializer->fromParser($parser);
//                }
//            }
//        } else {
//            $voutCount = $this->varint->read($parser);
//            for ($i = 0; $i < $voutCount; $i++) {
//                $vout[] = $this->outputSerializer->fromParser($parser);
//            }
//        }

        $vwit = [];
        // no witness data
//        if (($flags & 1)) {
//            $flags ^= 1;
//            $witCount = count($vin);
//            for ($i = 0; $i < $witCount; $i++) {
//                $vwit[] = $this->witnessSerializer->fromParser($parser);
//            }
//        }

//        if ($flags) {
//            throw new \RuntimeException('Flags byte was 0');
//        }

        $lockTime = (int) $this->uint32le->read($parser);

        if ($version >= 2) {
            $nJoinSplit = (int) $this->varint->read($parser);
            if ($nJoinSplit > 0) {
                $vJoinSplit = $parser->readBytes(1802 * $nJoinSplit);
            }
            $joinSplitPubKey = $parser->readBytes(32);
            $joinSplitSig    = $parser->readBytes(64);

        }

        return new Transaction($version, $vin, $vout, $vwit, $lockTime);
    }

    /**
     * @param BufferInterface $data
     * @return TransactionInterface
     */
    public function parse(BufferInterface $data): TransactionInterface
    {
        return $this->fromParser(new Parser($data));
    }

    /**
     * @param TransactionInterface $transaction
     * @param int $opt
     * @return BufferInterface
     */
    public function serialize(TransactionInterface $transaction, int $opt = 0): BufferInterface
    {
        $parser = new Parser();
        $parser->appendBinary($this->int32le->write($transaction->getVersion()));

        $flags = 0;
        $allowWitness = !($opt & self::NO_WITNESS);
        if ($allowWitness && $transaction->hasWitness()) {
            $flags |= 1;
        }

        if ($flags) {
            $parser->appendBinary(pack("CC", 0, $flags));
        }

        $parser->appendBinary($this->varint->write(count($transaction->getInputs())));
        foreach ($transaction->getInputs() as $input) {
            $parser->appendBuffer($this->inputSerializer->serialize($input));
        }

        $parser->appendBinary($this->varint->write(count($transaction->getOutputs())));
        foreach ($transaction->getOutputs() as $output) {
            $parser->appendBuffer($this->outputSerializer->serialize($output));
        }

        if ($flags & 1) {
            foreach ($transaction->getWitnesses() as $witness) {
                $parser->appendBuffer($this->witnessSerializer->serialize($witness));
            }
        }

        $parser->appendBinary($this->uint32le->write($transaction->getLockTime()));

        return $parser->getBuffer();
    }
}
