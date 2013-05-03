<?php

namespace CyberSpectrum\Transifex;

class Project extends BaseObject
{
	protected $slug;

	public function setSlug($slug)
	{
		$this->slug = $slug;
	}

	public function getSlug()
	{
		return $this->slug;
	}

	public function getResources()
	{
		$data = $this->executeJson(sprintf('project/%s/resources/', $this->ensureParameter('slug')));

		$resources = array();
		foreach ($data as $entry)
		{
			$resource = new Resource($this->getTransport());
			$resource->setProject($this);
			$resource->setFromResult($entry);
			$resources[$resource->getSlug()] = $resource;
		}

		return $resources;
	}

	public function getResource($slug)
	{
		$resource = new Resource($this->getTransport());

		$resource->setProject($this->getSlug());
		$resource->setSlug($slug);

		$resource->fetchDetails();

		return $resource;
	}
}