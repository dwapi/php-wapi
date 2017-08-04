<?php

namespace Wapi\Exception;

class AccessDenied extends WapiException  {
  const CODE = 2;
  const MESSAGE = 'Access denied.';
}