<?php
class ModuleLoader {
    private static $modules = [];
    
    public static function loadCoreModules() {
        $coreModules = [
            'Database' => 'Database',
            'Auth' => 'Auth'
        ];
        
        foreach ($coreModules as $className) {
            if (class_exists($className)) {
                // Special handling for Database singleton
                if ($className === 'Database') {
                    self::$modules[$className] = Database::getInstance();
                } else {
                    self::$modules[$className] = new $className();
                }
            }
        }
        
        return self::$modules;
    }
    
    public static function loadModule($moduleName) {
        $moduleFile = __DIR__ . '/../modules/' . $moduleName . '.php';
        
        if (file_exists($moduleFile)) {
            require_once $moduleFile;
            $className = $moduleName;
            
            if (class_exists($className)) {
                if (!isset(self::$modules[$className])) {
                    // Handle singleton classes like Database
                    if ($className === 'Database') {
                        self::$modules[$className] = Database::getInstance();
                    } else {
                        self::$modules[$className] = new $className();
                    }
                }
                return self::$modules[$className];
            }
        }
        
        return null;
    }
    
    public static function getModule($name) {
        return self::$modules[$name] ?? null;
    }
    
    public static function getAllModules() {
        return self::$modules;
    }
}
?>