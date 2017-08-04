<?php

namespace Wapi\Exception;

class ParametersInvalid extends WapiException  {
  const CODE = 8;
  const MESSAGE = 'Missing or invalid parameters.';
}