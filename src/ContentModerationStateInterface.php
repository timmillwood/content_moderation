<?php

namespace Drupal\content_moderation;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

interface ContentModerationStateInterface extends ContentEntityInterface, EntityOwnerInterface {

}
