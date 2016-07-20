<?php

namespace Hrmshandy\Finder\Helpers;

class Path
{

	 /**
     * Assembles a URL from an ordered list of segments
     *
     * @param mixed string  Open ended number of arguments
     * @return string
     */
    public static function assemble($args)
    {
        $args = func_get_args();
        if (is_array($args[0])) {
            $args = $args[0];
        }

        if (! is_array($args) || ! count($args)) {
            return null;
        }

        return self::tidy('/' . join($args, '/'));
    }

	/**
     * Removes occurrences of "//" in a $path (except when part of a protocol)
     *
     * @param string $path  Path to remove "//" from
     * @return string
     */
    public static function tidy($path)
    {
        return preg_replace('#(^|[^:])//+#', '\\1/', $path);
    }
}