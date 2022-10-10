<?php

namespace piyo2\mail;

use piyo2\util\cimap\CIMap;
use Normalizer;

class Header
{
	/** @var CIMap */
	protected $values;

	public function __construct()
	{
		$this->values = new CIMap();
	}

	/**
	 * ヘッダ値を設定する
	 *
	 * @param string $name
	 * @param string $value
	 * @return void
	 */
	public function set(string $name, string $value): void
	{
		$this->values->set($name, $value);
	}

	/**
	 * ヘッダ値を取得する
	 *
	 * @param string $name
	 * @return string|null
	 */
	public function get(string $name): ?string
	{
		return $this->values->get($name);
	}

	/**
	 * ヘッダを送信形式にする
	 *
	 * @return string
	 */
	public function render(): string
	{
		$lines = [];
		foreach ($this->values->toArray() as $name => $value) {
			if ($value === null) continue;
			$lines[] = $name . ': ' . self::encode($value);
		}
		$lines[] = '';
		return \implode(PHP_EOL, $lines);
	}

	/**
	 * Boundary クラスで生成したメッセージボディにはヘッダが少しくっついている。
	 * そのヘッダ部分をこのクラスで取り込み、残りの部分を吐き出す。
	 *
	 * @param string $body
	 * @return string
	 */
	public function eat(string $body): string
	{
		$lines = \explode(PHP_EOL, $body);
		do {
			$line = \array_shift($lines);
			if ($line === '') {
				break;
			}
			[$name, $value] = \array_pad(\explode(':', $line, 2), 2, '');
			$value = self::decode(ltrim($value));
			$this->set($name, $value);
		} while (\count($lines) > 0);

		return \implode(PHP_EOL, $lines);
	}

	/**
	 * ASCII 文字以外はエンコードする。UTF-8 のみ対応
	 *
	 * @param string $value
	 * @return string
	 */
	public static function encode(string $value): string
	{
		static $asciis = '\\r\\n\\x20-\\x7E';

		return \preg_replace_callback("/[$asciis]+|[^$asciis]+/", function ($matches) use ($asciis) {
			$str = $matches[0];
			if (\preg_match("/[^$asciis]|[?]/", $str)) {
				$str = Normalizer::normalize($str);
				return \mb_encode_mimeheader($str, 'UTF-8', 'B', PHP_EOL);
			} else {
				return $str;
			}
		}, $value);
	}

	public static function decode(string $value): string
	{
		return \mb_decode_mimeheader($value);
	}
}
