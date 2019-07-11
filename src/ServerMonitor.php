<?php
/**
 * Created by PhpStorm.
 * User: Sarfraz
 * Date: 7/11/2019
 * Time: 1:38 PM
 */

namespace Sarfraznawaz2005\ServerMonitor;


class ServerMonitor
{
    /**
     * Checks if current application environment is production.
     *
     * @return bool
     */
    protected function isProduction(): bool
    {
        $envs = config('server-monitor.production_environments');

        return in_array(config('app.env'), $envs, true);
    }

    /**
     * Returns all checks that need to be run.
     *
     * @return array
     */
    public function getChecks(): array
    {
        $serverChecks['server.checks'] = config('server-monitor.checks.server');
        $applicationCommonChecks['application.common.checks'] = config('server-monitor.checks.application.common');

        $key = 'application.' . config('app.env') . '.checks';

        if ($this->isProduction()) {
            $envChecks[$key] = config('server-monitor.checks.application.production');
        } else {
            $envChecks[$key] = config('server-monitor.checks.application.development');
        }

        $checks = array_merge($serverChecks, $applicationCommonChecks, $envChecks);

        return array_filter($checks);
    }

    /**
     * Runs all checks and returns results.
     *
     * @return array
     */
    public function runChecks(): array
    {
        $results = [];

        $checksAll = $this->getChecks();

        foreach ($checksAll as $type => $checks) {
            if ($checks) {
                foreach ($checks as $check => $config) {
                    if (!is_array($config)) {
                        $check = $config;
                        $config = [];
                    }

                    $object = new $check();
                    $name = $object->name();
                    $message = $object->message();
                    $result = $object->check($config);

                    $results[] = [
                        'type' => $type,
                        'checker' => $check,
                        'name' => $name,
                        'result' => $result,
                        'message' => $message,
                    ];
                }
            }
        }

        $results = collect($results)->groupBy('type')->toArray();

        return $results;
    }
}
