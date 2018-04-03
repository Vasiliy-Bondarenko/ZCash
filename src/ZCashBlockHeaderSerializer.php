<?php

declare(strict_types=1);

namespace ZCash;

use BitWasp\Bitcoin\Block\BlockHeader;
use BitWasp\Bitcoin\Block\BlockHeaderInterface;
use BitWasp\Bitcoin\Serializer\Block\BlockHeaderSerializer;
use BitWasp\Bitcoin\Serializer\Types;
use BitWasp\Buffertools\Exceptions\ParserOutOfRange;
use BitWasp\Buffertools\Parser;

class ZCashBlockHeaderSerializer extends BlockHeaderSerializer
{
    /**
     * @param Parser $parser
     * @return BlockHeaderInterface
     * @throws ParserOutOfRange
     */
    public function fromParser(Parser $parser): BlockHeaderInterface
    {
        try {
            $version        = (int) Types::uint32le()->read($parser);
            $hashPrevBlock  = Types::bytestringle(32)->read($parser);
            $hashMerkleRoot = Types::bytestringle(32)->read($parser);
            $hashReserved   = Types::bytestringle(32)->read($parser);
            $timestamp      = (int) Types::uint32le()->read($parser);
            $bits           = (int) Types::uint32le()->read($parser);

            // ZCash block header is very different from Bitcoin.
            // Specification: https://raw.githubusercontent.com/zcash/zips/master/protocol/protocol.pdf
            // on page 39


            $nonce = Types::bytestringle(32)->read($parser)->getHex();

            // nonce in ZCash is char32, while in bitcoin it's uint32_t
            // so to make BlockHeader happy - just set to something!
            $nonce = (int) $nonce;

            // basically we just skipping this fields to move parser caret to correct position to continue reading data after the header.
            // this vars are not used
            $solutionSize = $parser->readBytes(3);
            $solution     = $parser->readBytes(1344);

            return new BlockHeader(
                $version,
                $hashPrevBlock,
                $hashMerkleRoot,
                $timestamp,
                $bits,
                $nonce
            );
        } catch (ParserOutOfRange $e) {
            throw new ParserOutOfRange('Failed to extract full block header from parser');
        }
    }
}
