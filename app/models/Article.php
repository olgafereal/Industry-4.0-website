<?php

use Phalcon\Mvc\Model;
use Phalcon\Di;

class Article extends Model
{
    private $id;
    private $title_uk;
    private $title_en;
    private $text_uk;
    private $text_en;
    private $sort;
    private $image;
    private $author_id;

    function initialize()
    {
        if (Di::getDefault()->has('auth') && Di::getDefault()->get('auth')->isLoggedIn() === true) {
            $this->author_id = Di::getDefault()->get('auth')->getUser()->getId();
        }
        $this->sort = 100;
    }

    public function getSource(): string
    {
        return 'articles';
    }

    public function getId()
    {
        return $this->id;
    }

    public function getTitle($lang = null): string
    {
        $tmp_field = 'title_';

        if (is_null($lang) === true) {
            $lang_service = Di::getDefault()->get('lang');
            $lang = $lang_service->getCurrent();
        }

        if (gettype($lang) === 'string') {
            $tmp_field = $tmp_field . $lang;
        } else if ($lang instanceof Lang === true) {
            $tmp_field = $tmp_field . $lang->getId();
        } else {
            throw new TypeError('wrong lang type');
        }

        if (property_exists($this, $tmp_field) === false) {
            throw new UnexpectedValueException('unknown lang');
        }

        return $this->$tmp_field;
    }

    public function getSort(): int
    {
        return $this->sort;
    }

    public function getAuthorId(): int
    {
        return $this->author_id;
    }

    public function getAuthor()
    {
        $author = User::findFirst($this->author_id);
        if ($author === false) {
            return null;
        }

        return $author;
    }

    public function getText($lang = null): string
    {
        $tmp_field = 'text_';

        if (is_null($lang) === true) {
            $lang_service = Di::getDefault()->get('lang');
            $lang = $lang_service->getCurrent();
        }

        if (gettype($lang) === 'string') {
            $tmp_field = $tmp_field . $lang;
        } else if ($lang instanceof Lang === true) {
            $tmp_field = $tmp_field . $lang->getId();
        } else {
            throw new Exception('wrong lang');
        }

        return $this->$tmp_field;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function getImageLink()
    {
        if ($this->getImage() === null) {
            return null;
        }
        return Di::getDefault()->get('url')->getStatic('content/article/' . $this->getImage());
    }

    public function getLink(): string
    {
        return Di::getDefault()->get('url')->get([
            'for' => 'article/show',
            'language' => Di::getDefault()->get('lang')->getCurrent()->getId(),
            'article_id' => $this->getId(),
        ]);
    }

    public function setId(int $value)
    {
        $this->id = $value;
    }

    public function setTitle(string $value, $lang)
    {
        $tmp_field = 'title_';

        if (gettype($lang) === 'string') {
            $tmp_field = $tmp_field . $lang;
        } else if ($lang instanceof Lang === true) {
            $tmp_field = $tmp_field . $lang->getId();
        }

        $this->$tmp_field = $value;
    }

    public function setSort(int $sort)
    {
        $this->sort = $sort;
    }

    public function setText(string $value, $lang)
    {
        $tmp_field = 'text_';

        if (gettype($lang) === 'string') {
            $tmp_field = $tmp_field . $lang;
        } else if ($lang instanceof Lang === true) {
            $tmp_field = $tmp_field . $lang->getId();
        }

        $this->$tmp_field = $value;
    }

    public function setImage($value)
    {
        $this->image = $value;
    }

    public function setAuthorId(int $author_id)
    {
        $this->author_id = $author_id;
    }

    public function getLinkBackendEdit(): string
    {
        return Di::getDefault()
            ->get('url')
            ->get([
                'for' => 'backend/article/item_action',
                'action' => 'edit',
                'article_id' => $this->getId(),
            ]);
    }

    public function getLinkBackendDelete(): string
    {
        return Di::getDefault()
            ->get('url')
            ->get([
                'for' => 'backend/article/item_action',
                'action' => 'delete',
                'article_id' => $this->getId(),
            ]);
    }

    public function afterDelete()
    {
        $this->deleteImage();
    }

    public function deleteImage()
    {
        if (
            $this->getImage() !== null
            && \file_exists(CONTENT_PATH . 'article' . DIRECTORY_SEPARATOR . $this->getImage())
        ) {
            unlink(CONTENT_PATH . 'article' . DIRECTORY_SEPARATOR . $this->getImage());
        }
    }

    public function updateImage(Phalcon\Http\Request\File $file)
    {
        $this->deleteImage();

        $this->setImage($this->getId() . '.' . $file->getExtension());
        if (file_exists(CONTENT_PATH . 'article') === false) {
            mkdir(CONTENT_PATH . 'article');
        }
        $file->moveTo(
            CONTENT_PATH . 'article' . DIRECTORY_SEPARATOR . $this->getImage()
        );

        try {
            if ($this->save() === false) {
                throw new Exception();
            }
        } catch (Exception $e) {
            if (\file_exists(CONTENT_PATH . 'article' . DIRECTORY_SEPARATOR . $this->getImage())) {
                unlink(CONTENT_PATH . 'article' . DIRECTORY_SEPARATOR . $this->getImage());
            }
            throw new Exception('Can not save image.');
        }
    }
}
