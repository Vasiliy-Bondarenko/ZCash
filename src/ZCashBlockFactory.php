<?php

namespace ZCash;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Block\BlockFactory;
use BitWasp\Bitcoin\Block\BlockInterface;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Script\Opcodes;
use BitWasp\Bitcoin\Serializer\Block\BlockSerializer;
use BitWasp\Bitcoin\Serializer\Script\ScriptWitnessSerializer;
use BitWasp\Bitcoin\Serializer\Transaction\OutPointSerializer;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionInputSerializer;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionOutputSerializer;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\BufferInterface;


class ZCashBlockFactory extends BlockFactory
{
    /**
     * @param string $string
     * @param Math|null $math
     * @return BlockInterface
     * @throws \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     * @throws \Exception
     */
    public static function fromHex(string $string, Math $math = null): BlockInterface
    {
        return self::fromBuffer(Buffer::hex($string), $math);
    }

    /**
     * @param BufferInterface $buffer
     * @param Math|null $math
     * @return BlockInterface
     * @throws \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     */
    public static function fromBuffer(BufferInterface $buffer, Math $math = null): BlockInterface
    {
        $opcodes = new Opcodes();
        $serializer = new BlockSerializer(
            $math ?: Bitcoin::getMath(),
            new ZCashBlockHeaderSerializer(),
            new ZCashTransactionSerializer(
                new TransactionInputSerializer(new OutPointSerializer(), $opcodes),
                new TransactionOutputSerializer($opcodes),
                new ScriptWitnessSerializer()
            )
        );

        return $serializer->parse($buffer);
    }
}