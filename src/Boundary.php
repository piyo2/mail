<?php

namespace piyo2\mail;

use Normalizer;

class Boundary
{
	/** @var int 1行の長さ */
	const LINE_LENGTH = 76;

	/** @var string */
	public $contentType;

	/** @var ?string ファイル名。指定すると添付ファイルとなる */
	public $fileName;

	/** @var ?string */
	public $content;

	/** @var Boundary[] */
	protected $parts = [];

	/**
	 * プレーンテキストのパートを作成する
	 *
	 * @param string $content
	 * @return Boundary
	 */
	public static function plainText(string $content): Boundary
	{
		$b = new Boundary();
		$b->contentType = 'text/plain; charset=UTF-8';
		$b->content = $content;
		return $b;
	}

	/**
	 * HTMLパートを作成する
	 *
	 * @param string $content
	 * @return Boundary
	 */
	public static function html(string $content): Boundary
	{
		$b = new Boundary();
		$b->contentType = 'text/html; charset=UTF-8';
		$b->content = $content;
		return $b;
	}

	/**
	 * 添付ファイルパートを作成する
	 *
	 * @param Attachment $attachment
	 * @return Boundary
	 */
	public static function attachment(Attachment $attachment): Boundary
	{
		$b = new Boundary();
		$b->content = $attachment->getContent();
		$b->contentType = $attachment->getContentType() ?? 'application/octet-stream';
		$b->fileName = $attachment->getName();
		return $b;
	}

	public function addPart(Boundary $boundary): void
	{
		$this->parts[] = $boundary;
	}

	/**
	 * メール本文を送信形式にする
	 *
	 * @param BoundaryMark|null $bm
	 * @return string
	 */
	public function render(BoundaryMark $bm = null): string
	{
		if (!$bm) {
			$bm = new BoundaryMark();
		}
		$separator = $bm->next();

		$content = [];

		if (count($this->parts) === 0) {
			$name = isset($this->fileName) ? Header::encode($this->fileName) : null;
			if (isset($name)) {
				$content[] = 'Content-Type: ' . $this->contentType . '; name="' . $name . '"';
			} else {
				$content[] = 'Content-Type: ' . $this->contentType;
			}
			$content[] = 'Content-Transfer-Encoding: base64';
			if (isset($name)) {
				$content[] = 'Content-Disposition: attachment; filename="' . $name . '"';
			}
			$content[] = '';
			if (strpos($this->contentType, 'text/') === 0) {
				$content[] = $this->encodeText($this->content ?? '');
			} else {
				$content[] = $this->encodeBinary($this->content ?? '');
			}
		} else {
			$content[] = 'Content-Type: ' . $this->contentType . '; boundary="' . $separator . '"';
			$content[] = '';
			foreach ($this->parts as $part) {
				$content[] = '--' . $separator;
				$content[] = $part->render($bm);
			}
			$content[] = '--' . $separator . '--';
			$content[] = '';
		}

		return \implode(PHP_EOL, $content);
	}

	/**
	 * テキストをエンコードする
	 *
	 * @param string $body
	 * @return string
	 */
	protected function encodeText(string $body): string
	{
		// メール本文の改行コードは \r\n とする
		$body = Normalizer::normalize($body);
		$body = preg_replace('/\\r\\n?|\\n/', "\r\n", $body);
		$body = \base64_encode($body);
		return chunk_split($body, self::LINE_LENGTH, PHP_EOL);
	}

	/**
	 * バイナリをエンコードする
	 *
	 * @param string $body
	 * @return string
	 */
	protected function encodeBinary(string $body): string
	{
		$body = \base64_encode($body);
		return chunk_split($body, self::LINE_LENGTH, PHP_EOL);
	}
}
