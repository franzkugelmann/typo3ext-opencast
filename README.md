# TYPO3 Extension opencast

This extension adds support for Opencast videos to TYPO3 10 LTS.

## Installation

* Install extension via composer: `composer require uos/opencast`
* Go into BE module 'settings' and set `host` parameter

## Usage

* Create new content element that's capable of displaying videos, e.g. `textmedia`
* Hit button 'Add media file', paste Opencast video URL into 'Add new media asset' and 'Add media'
* In case it's been a valid Opencast video URL there should be a new `.opencast` file that you can click on

## Rendering

Currently Opencast videos are being rendered as iframes.
