<?php

namespace Wapi\Exception;

class MessageInvalid extends WapiException  {
  const CODE = 3;
  const MESSAGE = 'Invalid message.';
}