<?php
declare(strict_types=1);
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace OCA\PostIt\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * @method string getUserId() @method void setUserId(string $v)
 * @method string|null getContent() @method void setContent(?string $v)
 * @method string getColor() @method void setColor(string $v)
 * @method int getX() @method void setX(int $v) @method int getY() @method void setY(int $v)
 * @method int getW() @method void setW(int $v) @method int getH() @method void setH(int $v)
 * @method int getPinned() @method void setPinned(int $v)
 * @method int getArchived() @method void setArchived(int $v)
 * @method int getCreatedAt() @method void setCreatedAt(int $v) @method int getUpdatedAt() @method void setUpdatedAt(int $v)
 */
class Note extends Entity implements JsonSerializable {
	protected $userId = '';
	protected $content = '';
	protected $color = '';
	protected $x = 0;
	protected $y = 0;
	protected $w = 280;
	protected $h = 260;
	protected $pinned = 0;
	protected $archived = 0;
	protected $createdAt = 0;
	protected $updatedAt = 0;
	public function __construct() {
		foreach (['x', 'y', 'w', 'h', 'pinned', 'archived', 'createdAt', 'updatedAt'] as $f) $this->addType($f, 'integer');
	}
	public function jsonSerialize(): array {
		return ['id' => $this->getId(), 'content' => $this->content ?? '', 'color' => $this->color, 'x' => $this->x, 'y' => $this->y,
			'w' => $this->w, 'h' => $this->h, 'pinned' => (bool)$this->pinned, 'archived' => (bool)$this->archived, 'createdAt' => $this->createdAt, 'updatedAt' => $this->updatedAt];
	}
}
