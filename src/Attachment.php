<?php

namespace piyo2\mail;

use piyo2\util\path\Path;
use LogicException;
use RuntimeException;

/**
 * Mail 用の添付ファイルを表すクラス
 *
 * ```
 * // ファイルを指定して添付ファイルオブジェクトを作成
 * Attachment::file('path/to/file.pdf');
 *
 * // 添付時のファイル名を変更する場合
 * Attachment::file('path/to/file.pdf', 'attachment-file.pdf');
 *
 * // ファイルの内容をバイト列として指定して添付ファイルオブジェクトを作成
 * Attachment::content('content', 'file.txt');
 * ```
 */
class Attachment
{
	/** @var string|null */
	protected $path;

	/** @var string|null */
	protected $name;

	/** @var string|null */
	protected $contentType;

	/** @var string|null */
	protected $content;

	/**
	 * @param string $path
	 * @param string|null $name
	 * @return Attachment
	 */
	public static function fromFile(string $path, ?string $contentType = null, ?string $name = null): Attachment
	{
		return new Attachment([
			'path' => $path,
			'contentType' => $contentType,
			'name' => $name,
		]);
	}

	/**
	 * @param string $content
	 * @param string|null $name
	 * @return Attachment
	 */
	public static function fromContent(string $content, ?string $contentType = null, ?string $name = null): Attachment
	{
		return new Attachment([
			'content' => $content,
			'contentType' => $contentType,
			'name' => $name,
		]);
	}

	/**
	 * @param mixed[] $opts
	 */
	public function __construct(array $opts)
	{
		if (isset($opts['content'])) {
			$this->path = null;
			$this->content = $opts['content'];
			$this->name = isset($opts['name']) ? Path::sanitizeFileName($opts['name']) : null;
			$this->contentType = $opts['contentType'];
		} else if (isset($opts['path'])) {
			$this->path = $opts['path'];
			$this->content = null;
			$this->name = isset($opts['name']) ? Path::sanitizeFileName($opts['name']) : null;
			$this->contentType = $opts['contentType'];
		} else {
			throw new LogicException('Constructor must be called by static methods.');
		}
	}

	/**
	 * @return ?string
	 */
	public function getName(): ?string
	{
		return $this->name;
	}

	/**
	 * @return ?string
	 */
	public function getContentType(): ?string
	{
		return $this->contentType;
	}

	/**
	 * @return string
	 */
	public function getContent(): string
	{
		if (isset($this->content)) {
			return $this->content;
		} else {
			$content = file_get_contents($this->path);
			if ($content === false) {
				throw new RuntimeException('Could not read file: ' . $this->path);
			}
			return $content;
		}
	}

	/**
	 * ファイル名を変更する。
	 * メソッドチェーン用に自分自身のインスタンスを返す
	 *
	 * @param string $name
	 * @return Attachment
	 */
	public function rename(string $name): Attachment
	{
		$this->name = Path::sanitizeFileName($name);
		return $this;
	}
}
