{ pkgs, ... }:

{
  languages.php.enable = true;
  languages.php.extensions = [ "xdebug" ];
}
