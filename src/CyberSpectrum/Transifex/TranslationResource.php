<?php

/**
 * This toolbox provides easy ways to generate .xlf (XLIFF) files from Contao language files, push them to transifex
 * and pull translations from transifex and convert them back to Contao language files.
 *
 * @package      cyberspectrum/contao-toolbox
 * @author       Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright    CyberSpectrum
 * @license      LGPL-3.0+.
 * @filesource
 */

namespace CyberSpectrum\Transifex;

/**
 * This class abstracts a resource on transifex.
 */
class TranslationResource extends BaseObject
{
    /**
     * The project slug.
     *
     * @var string
     */
    protected $project;

    /**
     * The resource slug.
     *
     * @var string
     */
    protected $slug;

    /**
     * The name of the resource.
     *
     * @var string
     */
    protected $name;

    /**
     * The language code of the source language.
     *
     * @var string
     */
    protected $sourceLanguageCode;

    /**
     * The internationalization type.
     *
     * @var string
     */
    protected $i18nType;

    /**
     * The categories.
     *
     * @var string
     */
    protected $categories;

    /**
     * The content.
     *
     * @var string
     */
    protected $content;

    /**
     * The MIME type.
     *
     * @var string
     */
    protected $mimetype;

    /**
     * Creation date (read only).
     *
     * @var string
     */
    protected $created;

    /**
     * List of available languages (read only).
     *
     * @var string
     */
    protected $availableLanguages;

    /**
     * Amount of words contained in this resource (read only).
     *
     * @var int
     */
    protected $wordcount;

    /**
     * Amount of total entities (read only).
     *
     * @var int
     */
    protected $totalEntities;

    /**
     * Flag if this resource accepts translations (read only).
     *
     * @var bool
     */
    protected $acceptTranslations;

    /**
     * Date when this resource has last been updated (read only).
     *
     * @var string
     */
    protected $lastUpdate;

    /**
     * Create a new instance.
     *
     * @param Transport $transport The transport.
     */
    public function __construct(Transport $transport)
    {
        parent::__construct($transport);

        // We will use this almost ever?
        $this->setI18nType('XLIFF');
        $this->setSourceLanguageCode('en');
        $this->setMimetype('text/xml');
    }

    /**
     * Set the project.
     *
     * @param Project|string $project The project or project slug.
     *
     * @return \CyberSpectrum\Transifex\TranslationResource
     */
    public function setProject($project)
    {
        if ($project instanceof Project) {
            $this->project = $project->getSlug();

            return $this;
        }

        $this->project = (string) $project;

        return $this;
    }

    /**
     * Retrieve the project slug.
     *
     * @return string
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * Set the slug.
     *
     * @param string $slug The slug name.
     *
     * @return \CyberSpectrum\Transifex\TranslationResource
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Retrieve the slug.
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set the categories.
     *
     * @param string $categories The categories.
     *
     * @return \CyberSpectrum\Transifex\TranslationResource
     */
    public function setCategories($categories)
    {
        $this->categories = $categories;

        return $this;
    }

    /**
     * Retrieve the categories.
     *
     * @return string
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * Set the intenternationalization type.
     *
     * @param string $i18nType The type name.
     *
     * @return \CyberSpectrum\Transifex\TranslationResource
     */
    public function setI18nType($i18nType)
    {
        $this->i18nType = $i18nType;

        return $this;
    }

    /**
     * Get the intenternationalization type.
     *
     * @return string
     */
    public function getI18nType()
    {
        return $this->i18nType;
    }

    /**
     * Set the name of the resource.
     *
     * @param string $name The name.
     *
     * @return \CyberSpectrum\Transifex\TranslationResource
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Retrieve the name of the resource.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the source language code.
     *
     * @param string $sourceLanguageCode The language code.
     *
     * @return \CyberSpectrum\Transifex\TranslationResource
     */
    public function setSourceLanguageCode($sourceLanguageCode)
    {
        $this->sourceLanguageCode = $sourceLanguageCode;

        return $this;
    }

    /**
     * Retrieve the source language code.
     *
     * @return string
     */
    public function getSourceLanguageCode()
    {
        return $this->sourceLanguageCode;
    }

    /**
     * Set the content.
     *
     * @param string $content The file content.
     *
     * @return \CyberSpectrum\Transifex\TranslationResource
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Retrieve the content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set the mime type.
     *
     * @param string $mimetype The MIME type.
     *
     * @return \CyberSpectrum\Transifex\TranslationResource
     */
    public function setMimetype($mimetype)
    {
        $this->mimetype = $mimetype;

        return $this;
    }

    /**
     * Retrieve the mime type.
     *
     * @return string
     */
    public function getMimetype()
    {
        return $this->mimetype;
    }

    /**
     * Get the accept language flag.
     *
     * @return bool
     */
    public function hasAcceptTranslations()
    {
        return $this->acceptTranslations;
    }

    /**
     * Get the available languages.
     *
     * @return string
     */
    public function getAvailableLanguages()
    {
        return $this->availableLanguages;
    }

    /**
     * Get the date of creation.
     *
     * @return string
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Get the date of last update.
     *
     * @return string
     */
    public function getLastUpdate()
    {
        return $this->lastUpdate;
    }

    /**
     * Get the amount of total entities.
     *
     * @return int
     */
    public function getTotalEntities()
    {
        return $this->totalEntities;
    }

    /**
     * Get the word count.
     *
     * @return int
     */
    public function getWordcount()
    {
        return $this->wordcount;
    }

    /**
     * Set all values from the passed result.
     *
     * @param array $data The data to initialize from.
     *
     * @return \CyberSpectrum\Transifex\TranslationResource
     */
    public function setFromResult($data)
    {
        $this->setCategories($data['categories']);
        $this->setI18nType($data['i18n_type']);
        $this->setSourceLanguageCode($data['source_language_code']);
        $this->setName($data['name']);
        $this->setSlug($data['slug']);

        if (isset($data['project_slug'])) {
            $this->setProject($data['project_slug']);
        }

        if (isset($data['created'])) {
            $this->created = $data['created'];
        }

        if (isset($data['available_languages'])) {
            $languages = array();
            foreach ($data['available_languages'] as $lang) {
                $languages[$lang['code']] = array
                (
                    $lang['code_aliases'],
                    $lang['name'],
                );
            }
            $this->availableLanguages = $languages;
        }

        if (isset($data['wordcount'])) {
            $this->wordcount = $data['wordcount'];
        }

        if (isset($data['total_entities'])) {
            $this->totalEntities = $data['total_entities'];
        }

        if (isset($data['accept_translations'])) {
            $this->acceptTranslations = $data['accept_translations'];
        }

        if (isset($data['last_update'])) {
            $this->lastUpdate = $data['last_update'];
        }

        return $this;
    }

    /**
     * Create the resource on transifex.
     *
     * @return \CyberSpectrum\Transifex\TranslationResource
     */
    public function create()
    {
        $params = array(
            'slug' => $this->ensureParameter('slug'),
            'name' => $this->ensureParameter('name'),
            'i18n_type' => $this->ensureParameter('i18nType'),
            'content' => $this->ensureParameter('content'),
        );

        $this->post(
            sprintf('project/%s/resources/', $this->ensureParameter('project')),
            $params
        );

        return $this;
    }

    /**
     * Update the content on transifex.
     *
     * @return \CyberSpectrum\Transifex\TranslationResource
     */
    public function updateContent()
    {
        $params = array(
            'content' => $this->ensureParameter('content'),
        );

        $this->put(
            sprintf(
                'project/%s/resource/%s/content/',
                $this->ensureParameter('project'),
                $this->ensureParameter('slug')
            ),
            $params
        );

        return $this;
    }

    /**
     * Retrieve the details of the resource.
     *
     * @return \CyberSpectrum\Transifex\TranslationResource
     */
    public function fetchDetails()
    {
        $response = $this->executeJson(
            sprintf(
                'project/%s/resource/%s',
                $this->ensureParameter('project'),
                $this->ensureParameter('slug')
            ),
            array('details' => '')
        );

        $this->setFromResult($response);

        return $this;
    }

    /**
     * Fetch the file content.
     *
     * @return \CyberSpectrum\Transifex\TranslationResource
     */
    public function fetchContent()
    {
        $response = $this->executeJson(
            sprintf(
                'project/%s/resource/%s/content/',
                $this->ensureParameter('project'),
                $this->ensureParameter('slug')
            )
        );

        $this->setContent($response['content']);
        $this->setMimetype($response['mimetype']);

        return $this;
    }

    /**
     * Fetch a certain translation of the resource.
     *
     * @param string $langcode The language code of the language to retrieve.
     *
     * @param string $mode     The translation mode to use ('reviewed', 'translated' or 'default).
     *
     * @return string
     */
    public function fetchTranslation($langcode, $mode = 'reviewed')
    {
        $parameters = array(
            'file' => '',
            'mode' => $mode
        );

        return $this->execute(
            sprintf(
                'project/%s/resource/%s/translation/%s',
                $this->ensureParameter('project'),
                $this->ensureParameter('slug'),
                $langcode
            ),
            $parameters
        );
    }
}
