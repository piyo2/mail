<?php

namespace piyo2\mail;

use InvalidArgumentException;
use LogicException;

class Mail
{
	/** @var string 件名 */
	protected $subject;

	/** @var Header 追加ヘッダ */
	protected $headers;

	/** @var string メッセージ本文 */
	protected $message;

	/** @var string HTMLメッセージ本文 */
	protected $htmlMessage;

	/** @var Attachment[] 添付ファイル */
	protected $attachments = [];

	/** @var string PHP の mail() 関数の第5引数 */
	protected $additionalParameters = null;

	public function __construct()
	{
		$this->headers = new Header();
		$this->headers->set('MIME-Version', '1.0');
	}

	/**
	 * メールを送信する
	 *
	 * @param string $aTo 送信先のメールアドレス
	 * @return bool 成功したか場合は true
	 */
	public function send(string $aTo): bool
	{
		if (!isset($this->subject, $this->message)) {
			throw new LogicException('Subject or message was not set.');
		}
		list($to, $subject, $message, $headers, $additionalParameters) = $this->buildParameters($aTo);
		return mail($to, $subject, $message, $headers, $additionalParameters);
	}

	/**
	 * 送信者を設定する
	 *
	 * @param string $email
	 * @param string $name
	 * @return void
	 */
	public function from(string $email, string $name = ''): void
	{
		if (trim($name) === '') {
			$value = $email;
		} else if (strpos($name, ',') !== false) {
			$value = "\"$name\" <{$email}>";
		} else {
			$value = "$name <{$email}>";
		}
		$this->header('From', $value);

		if ($this->additionalParameters) {
			$this->additionalParameters .= ' -f ' . $email;
		} else {
			$this->additionalParameters = '-f ' . $email;
		}
	}

	/**
	 * 件名を設定する
	 *
	 * @param string $subject
	 * @return void
	 */
	public function subject(string $subject): void
	{
		$this->subject = $subject;
	}

	/**
	 * メッセージを設定する
	 *
	 * @param string $message
	 * @return void
	 */
	public function message(string $message): void
	{
		$this->message = $message;
	}

	/**
	 * HTMLメッセージを設定する
	 *
	 * @param string $html
	 * @return void
	 */
	public function htmlMessage(string $html): void
	{
		$this->htmlMessage = $html;
	}

	/**
	 * ヘッダを追加する
	 *
	 * @param string $name
	 * @param string $value
	 * @return void
	 */
	public function header(string $name, string $value): void
	{
		$this->headers->set($name, $value);
	}

	/**
	 * 送信パラメータを設定する
	 *
	 * @param string $param
	 * @return void
	 */
	public function setAdditionalParameters(string $param): void
	{
		$this->additionalParameters = $param;
	}

	/**
	 * 添付ファイルを追加する
	 *
	 * @param Attachment $attachment
	 * @return void
	 */
	public function attach(Attachment $attachment): void
	{
		if (!($attachment instanceof Attachment)) {
			throw new InvalidArgumentException('Invalid attachment');
		}
		$this->attachments[] = $attachment;
	}

	/**
	 * mail() 関数に渡すパラメータを組み立てる
	 *
	 * @param string $to 送信先メールアドレス
	 * @return string[] 次の配列: [$to, $subject, $message, $headers, $additionalParameters]
	 */
	protected function buildParameters(string $to): array
	{
		$body = $this->buildBody();
		$headers = clone $this->headers;
		$body = $headers->eat($body);

		return \array_map([$this, 'eol'], [
			Header::encode($to),
			Header::encode($this->subject),
			$body,
			$headers->render(),
			$this->additionalParameters,
		]);
	}

	/**
	 * 改行コードを mail() 用に変換する
	 *
	 * @param string $str
	 * @return string
	 */
	protected function eol(string $str): string
	{
		return str_replace(PHP_EOL, "\n", $str);
	}

	/**
	 * メッセージ本文をエンコードして返す
	 *
	 * @return string
	 */
	protected function buildBody(): string
	{
		$boundary = $this->buildBoundary();
		return $boundary->render();
	}

	/**
	 * Boundary を生成する
	 *
	 * @return Boundary
	 */
	protected function buildBoundary(): Boundary
	{
		if (count($this->attachments) === 0) {
			// 添付なし
			return $this->buildMessageBoundary();
		} else {
			// 添付あり
			$root = new Boundary();
			$root->contentType = 'multipart/mixed';
			$root->addPart($this->buildMessageBoundary());
			foreach ($this->attachments as $attachment) {
				$root->addPart(Boundary::attachment($attachment));
			}
			return $root;
		}
	}

	/**
	 * メッセージ部分の Boundary を生成する
	 *
	 * @return Boundary
	 */
	protected function buildMessageBoundary(): Boundary
	{
		if (!isset($this->htmlMessage)) {
			// テキストのみ
			return Boundary::plainText($this->message);
		} else {
			// HTMLメール
			$root = new Boundary();
			$root->contentType = 'multipart/alternative';
			$root->addPart(Boundary::plainText($this->message));
			$root->addPart(Boundary::html($this->htmlMessage));
			return $root;
		}
	}
}
