<?php

namespace Wapi\Exception;

class MethodNotFound extends WapiException  {
  const CODE = 4;
  const MESSAGE = 'Method not found.';
}
