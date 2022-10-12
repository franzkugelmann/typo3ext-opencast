<?php
namespace Uos\Opencast\Helpers;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\AbstractOnlineMediaHelper;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class OpencastHelper extends AbstractOnlineMediaHelper
{
    protected $extension = 'opencast';

    protected $host;

    private const MEDIA_ID_PATTERN = '([0-9a-f\-]+)';

    private const PATH_PATTERNS = [
        'paella\/ui\/watch\.html\?id=' . self::MEDIA_ID_PATTERN,
        'play\/' . self::MEDIA_ID_PATTERN,
    ];

    public function __construct($extension)
    {
        $this->extension = $extension;
        $this->host = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('opencast', 'host');
        $this->host = rtrim($this->host, '/') . '/';
    }

    /**
     * Try to transform given URL to a File
     *
     * @param string $url
     * @param Folder $targetFolder
     * @return File|null
     */
    public function transformUrlToFile($url, Folder $targetFolder)
    {
        if ($this->host) {
            // Prepare host for regex
            $hostPattern = str_replace(['/', '.'], ['\/', '\.'], $this->host);

            foreach (self::PATH_PATTERNS as $pathPattern) {
                if (preg_match('/^' . $hostPattern . $pathPattern . '$/i', $url, $match)) {
                    $mediaId = $match[1];

                    $file = $this->findExistingFileByOnlineMediaId(
                        $mediaId,
                        $targetFolder,
                        $this->extension
                    );

                    // no existing file create new
                    if ($file === null) {
                        $filename = $this->getTitle($mediaId) . '.' . $this->extension;

                        $file = $this->createNewFile(
                            $targetFolder, // folder
                            $filename,     // filename
                            $mediaId       // content
                        );
                    }

                    return $file;
                }
            }
        } else {
            DebugUtility::debug(
                'Please make sure the \'host\' is defined within the extension configuration of EXT:opencast',
            );
            die();
        }
    }

    /**
     * Get public url
     *
     * Return NULL if you want to use core default behaviour
     *
     * @param File $file
     * @param bool $relativeToCurrentScript
     * @return string|null
     */
    public function getPublicUrl(File $file, $relativeToCurrentScript = false)
    {
        return null;
    }

    /**
     * Get local absolute file path to preview image
     *
     * Return an empty string when no preview image is available
     *
     * @param File $file
     * @return string
     */
    public function getPreviewImage(File $file)
    {
        $mediaId = $this->getOnlineMediaId($file);
        $temporaryFileName = $this->getTempFolderPath() . 'opencast_' . md5($mediaId) . '.png';

        if (!file_exists($temporaryFileName)) {
            $attachments = $this->getAttachments($mediaId);
            foreach ($attachments ?? [] as $attachment) {
                $previewImage = false;
                if ($attachment['type'] === 'presenter/player+preview') {
                    $previewImage = GeneralUtility::getUrl($attachment['url']);
                }
                if ($previewImage !== false) {
                    file_put_contents($temporaryFileName, $previewImage);
                    GeneralUtility::fixPermissions($temporaryFileName);
                    break;
                }
            }
        }

        return $temporaryFileName;
    }

    /**
     * Get meta data for OnlineMedia item
     *
     * See $GLOBALS[TCA][sys_file_metadata][columns] for possible fields to fill/use
     *
     * @param File $file
     * @return array with metadata
     */
    public function getMetaData(File $file)
    {
        $mediaId = $this->getOnlineMediaId($file);
        $metadata = $this->fetchMetaData($mediaId);

        return $metadata;
    }

    protected function getTitle($mediaId): string
    {
        return $this->fetchMetaData($mediaId)['title'];
    }

    protected function fetchMetaData($mediaId): array
    {
        $metadata = [];

        if ($data = $this->fetchJson($mediaId)) {
            $metadata['title'] = $data['dcTitle'];
            $metadata['creator'] = $data['dcCreator'];
            $metadata['publisher'] = $data['dcPublisher'];
            $metadata['content_creation_date'] = strtotime($data['dcCreated']);
            $metadata['content_modification_date'] = strtotime($data['modified']);
            $metadata['keywords'] = $data['keywords'];
            if ($data['mediapackage']) {
                $metadata['duration'] = $data['mediapackage']['duration'] ?? 0;
            }
        } else {
            // Fallback: most basic information we've got!
            $metadata['title'] = $mediaId;
        }

        return $metadata;
    }

    protected function getAttachments($mediaId): ?array
    {
        if ($data = $this->fetchJson($mediaId)) {
            if (is_array($data['mediapackage']) &&
                is_array($data['mediapackage']['attachments']) &&
                is_array($data['mediapackage']['attachments']['attachment'])) {
                return $data['mediapackage']['attachments']['attachment'];
            }
        }

        return null;
    }

    protected function fetchJson($mediaId): ?array
    {
        if (preg_match('/' . self::MEDIA_ID_PATTERN . '/', $mediaId)) {
            $url = $this->host . 'search/episode.json?id=' . $mediaId;
            if ($json = GeneralUtility::getUrl($url)) {
                $json = json_decode($json, true);
                if (is_array($json['search-results']) &&
                    is_array($json['search-results']['result'])) {
                    return $json['search-results']['result'];
                }
            }
        }

        return null;
    }
}
