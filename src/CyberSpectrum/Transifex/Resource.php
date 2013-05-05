<?php


namespace CyberSpectrum\Transifex;

class Resource extends BaseObject
{
	protected $project;

	protected $slug;

	protected $name;

	protected $source_language_code;

	protected $i18n_type;

	protected $category;

	protected $content;

	protected $mimetype;

	// read only.

	protected $created;

	protected $available_languages;

	protected $wordcount;

	protected $total_entities;

	protected $accept_translations;

	protected $last_update;

	public function __construct(Transport $transport)
	{
		parent::__construct($transport);

		// We will use this almost ever?
		$this->setI18nType('XLIFF');
		$this->setSourceLanguageCode('en');
		$this->setMimetype('text/xml');
	}

	public function setProject($project)
	{
		if (is_string($project))
		{
			$this->project = $project;
		}
		else
		{
			/** @var Project $project */
			$this->project = $project->getSlug();
		}
	}

	public function getProject()
	{
		return $this->getProject();
	}

	public function setSlug($slug)
	{
		$this->slug = $slug;
	}

	public function getSlug()
	{
		return $this->slug;
	}

	public function setCategory($category)
	{
		$this->category = $category;
	}

	public function getCategory()
	{
		return $this->category;
	}

	public function setI18nType($i18n_type)
	{
		$this->i18n_type = $i18n_type;
	}

	public function getI18nType()
	{
		return $this->i18n_type;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setSourceLanguageCode($source_language_code)
	{
		$this->source_language_code = $source_language_code;
	}

	public function getSourceLanguageCode()
	{
		return $this->source_language_code;
	}

	public function setContent($content)
	{
		$this->content = $content;
	}

	public function getContent()
	{
		return $this->content;
	}

	public function setMimetype($mimetype)
	{
		$this->mimetype = $mimetype;
	}

	public function getMimetype()
	{
		return $this->mimetype;
	}

	public function getAcceptTranslations()
	{
		return $this->accept_translations;
	}

	public function getAvailableLanguages()
	{
		return $this->available_languages;
	}

	public function getCreated()
	{
		return $this->created;
	}

	public function getLastUpdate()
	{
		return $this->last_update;
	}

	public function getTotalEntities()
	{
		return $this->total_entities;
	}

	public function getWordcount()
	{
		return $this->wordcount;
	}

	public function setFromResult($data)
	{
		$this->setCategory($data['category']);
		$this->setI18nType($data['i18n_type']);
		$this->setSourceLanguageCode($data['source_language_code']);
		$this->setName($data['name']);
		$this->setSlug($data['slug']);

		if (isset($data['project_slug']))
		{
			$this->setProject($data['project_slug']);
		}

		if (isset($data['created']))
		{
			$this->created = $data['created'];
		}

		if (isset($data['available_languages']))
		{
			$languages = array();
			foreach ($data['available_languages'] as $lang)
			{
				$languages[$lang['code']] = array
				(
					$lang['code_aliases'],
					$lang['name'],
				);
			}
			$this->available_languages = $languages;
		}

		if (isset($data['wordcount']))
		{
			$this->wordcount = $data['wordcount'];
		}

		if (isset($data['total_entities']))
		{
			$this->total_entities = $data['total_entities'];
		}

		if (isset($data['accept_translations']))
		{
			$this->accept_translations = $data['accept_translations'];
		}

		if (isset($data['last_update']))
		{
			$this->last_update = $data['last_update'];
		}
	}


	public function create()
	{
		$params = array(
			'slug'      => $this->ensureParameter('slug'),
			'name'      => $this->ensureParameter('name'),
			'i18n_type' => $this->ensureParameter('i18n_type'),
			'content'   => $this->ensureParameter('content'),
		);

		$this->POST(
			sprintf('project/%s/resources/', $this->ensureParameter('project')),
			$params
		);
	}

	public function updateContent()
	{
		$params = array(
			'content'   => $this->ensureParameter('content'),
		);

		$this->PUT(
			sprintf('project/%s/resource/%s/content/', $this->ensureParameter('project'), $this->ensureParameter('slug')),
			$params
		);
	}

	public function fetchDetails()
	{
		$response = $this->executeJson(sprintf(
			'project/%s/resource/%s',
			$this->ensureParameter('project'),
			$this->ensureParameter('slug')
		), array('details' => '1'));

		$this->setFromResult($response);
	}

	public function fetchContent()
	{
		$response = $this->executeJson(sprintf(
			'project/%s/resource/%s/content/',
			$this->ensureParameter('project'),
			$this->ensureParameter('slug')
		));

		$this->setContent($response['content']);
		$this->setMimetype($response['mimetype']);
	}

	public function fetchTranslation($langcode, $mode = 'reviewed')
	{
		$parameters = array(
			'file' => 1,
			'mode' => $mode
		);

		return $this->execute(sprintf(
			'project/%s/resource/%s/translation/%s',
			$this->ensureParameter('project'),
			$this->ensureParameter('slug'),
			$langcode
		), $parameters);
	}
}