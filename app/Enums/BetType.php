<?php

namespace App\Enums;

enum BetType: string
{
    case TANSHO = 'tansho';
    case FUKUSHO = 'fukusho';
    case UMAREN = 'umaren';
    case WIDE = 'wide';
    case UMATAN = 'umatan';
    case SANRENPUKU = 'sanrenpuku';
    case SANRENTAN = 'sanrentan';
    case WAKUREN = 'wakuren';

    public function label(): string
    {
        return match ($this) {
            self::TANSHO => '単勝',
            self::FUKUSHO => '複勝',
            self::UMAREN => '馬連',
            self::WIDE => 'ワイド',
            self::UMATAN => '馬単',
            self::SANRENPUKU => '三連複',
            self::SANRENTAN => '三連単',
            self::WAKUREN => '枠連',
        };
    }

    public function scope(): string
    {
        return $this === self::WAKUREN ? 'frame' : 'horse';
    }

    public function picks(): int
    {
        return match ($this) {
            self::TANSHO, self::FUKUSHO => 1,
            self::UMAREN, self::WIDE, self::UMATAN, self::WAKUREN => 2,
            self::SANRENPUKU, self::SANRENTAN => 3,
        };
    }

    public function separator(): string
    {
        return match ($this) {
            self::UMATAN, self::SANRENTAN => '>',
            default => '-',
        };
    }

    public function defaultRows(): int
    {
        return match ($this) {
            self::TANSHO, self::FUKUSHO => 3,
            self::UMAREN, self::WIDE, self::UMATAN, self::WAKUREN => 5,
            self::SANRENPUKU, self::SANRENTAN => 6,
        };
    }

    public static function all(): array
    {
        return [
            self::TANSHO,
            self::FUKUSHO,
            self::WAKUREN,
            self::UMAREN,
            self::WIDE,
            self::UMATAN,
            self::SANRENPUKU,
            self::SANRENTAN,
        ];
    }


    public function order(): int
    {
        return match ($this) {
            self::TANSHO     => 1,
            self::FUKUSHO    => 2,
            self::WAKUREN    => 3,
            self::UMAREN     => 4,
            self::WIDE       => 5,
            self::UMATAN     => 6,
            self::SANRENPUKU => 7,
            self::SANRENTAN  => 8,
        };
    }
}
