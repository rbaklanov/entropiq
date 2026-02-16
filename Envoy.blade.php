@servers(['stage' => ['deployer@stage-server-ip'], 'production' => ['deployer@prod-server-ip']])

@setup
    $baseDir = '/var/www';

    if ($on === 'stage') {
        $appDir = $baseDir . '/entropiq-stage';
        $branch = 'stage';
        $composeFile = 'docker-compose.stage.yml';
    } elseif ($on === 'production') {
        $appDir = $baseDir . '/entropiq-prod';
        $branch = 'main';
        $composeFile = 'docker-compose.prod.yml';
    }
@endsetup

@story('deploy', ['on' => $on])
    pull
    build
    migrate
    cache
    health
@endstory

@task('pull')
    echo "Pulling latest changes..."
    cd {{ $appDir }}
    git pull origin {{ $branch }}
@endtask

@task('build')
    echo "Building and restarting containers..."
    cd {{ $appDir }}
    docker compose -f {{ $composeFile }} up -d --build
@endtask

@task('migrate')
    echo "Running migrations..."
    cd {{ $appDir }}
    docker compose -f {{ $composeFile }} exec -T app php artisan migrate --force
@endtask

@task('cache')
    echo "Clearing and warming caches..."
    cd {{ $appDir }}
    docker compose -f {{ $composeFile }} exec -T app php artisan config:cache
    docker compose -f {{ $composeFile }} exec -T app php artisan route:cache
    docker compose -f {{ $composeFile }} exec -T app php artisan view:cache
    docker compose -f {{ $composeFile }} exec -T app php artisan event:cache
@endtask

@task('health')
    echo "Running health check..."
    cd {{ $appDir }}
    sleep 5
    docker compose -f {{ $composeFile }} ps
    echo ""
    echo "Checking HTTP response..."
    curl -sf http://localhost:8080/up && echo " OK" || echo " FAILED"
@endtask

@task('rollback')
    echo "Rolling back last migration..."
    cd {{ $appDir }}
    docker compose -f {{ $composeFile }} exec -T app php artisan migrate:rollback --force
@endtask

@task('logs')
    echo "Showing app logs..."
    cd {{ $appDir }}
    docker compose -f {{ $composeFile }} logs --tail=100 app
@endtask

@task('horizon-restart')
    echo "Restarting Horizon..."
    cd {{ $appDir }}
    docker compose -f {{ $composeFile }} exec -T app php artisan horizon:terminate
@endtask

@finished
    echo "Finished at $(date)"
@endfinished
