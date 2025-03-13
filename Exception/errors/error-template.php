<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - Ace Framework</title>
    <style><?= $cssStyles ?></style>
</head>
<body>
    <div class="error-container">
        <div class="error-header">
            <div>
                <h1 class="error-title">
                    <?= htmlspecialchars($title) ?>
                    <span class="error-code"><?= htmlspecialchars($errorCode) ?></span>
                </h1>
                <p class="error-message"><?= htmlspecialchars($message) ?></p>
            </div>
            <div class="env-item">
                <span class="env-item-label">Environment</span>
                <span class="env-item-value badge badge-info"><?= htmlspecialchars($this->environment) ?></span>
            </div>
        </div>

        <div class="error-location">
            <div class="error-location-file">
                <?= htmlspecialchars($file) ?> : <?= $line ?>
            </div>
            <button class="copy-button" data-copy="<?= htmlspecialchars($file) ?>:<?= $line ?>">Copy location</button>
        </div>

        <div class="solutions">
            <h3 class="solutions-title">Possible Solutions</h3>
            <?php $solutions = $this->getSuggestedSolutions($exception); ?>
            <?php if (empty($solutions)): ?>
                <div class="solution-item">
                    Check the above error message and trace to identify the source of the problem.
                </div>
            <?php else: ?>
                <?php foreach ($solutions as $solution): ?>
                    <div class="solution-item">
                        <?= $solution ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="error-tabs">
            <div class="error-tab active" data-target="stack-tab">Stack Trace</div>
            <div class="error-tab" data-target="snippet-tab">Code Snippet</div>
            <div class="error-tab" data-target="request-tab">Request</div>
            <div class="error-tab" data-target="env-tab">Environment</div>
        </div>

        <div id="stack-tab" class="error-content active">
            <div class="stack-trace">
                <?php foreach ($frames as $index => $frame): ?>
                    <div class="stack-frame">
                        <div class="stack-frame-header">
                            <div class="stack-frame-function">
                                <?php if (isset($frame['class'])): ?>
                                    <?= htmlspecialchars($frame['class'] . $frame['type'] . $frame['function']) ?>
                                <?php else: ?>
                                    <?= htmlspecialchars($frame['function'] ?? 'Unknown function') ?>
                                <?php endif; ?>
                                (<?= implode(', ', $frame['args'] ?? []) ?>)
                            </div>
                            <div class="stack-frame-location">
                                <?= htmlspecialchars($frame['file']) ?>:<?= $frame['line'] ?>
                            </div>
                        </div>
                        <div class="stack-frame-content">
                            <?php if (!empty($frame['snippet'])): ?>
                                <div class="code-snippet">
                                    <pre><?php
                                    foreach ($frame['snippet'] as $lineNumber => $code) {
                                        $isHighlighted = $lineNumber === $frame['line'];
                                        echo '<div' . ($isHighlighted ? ' class="highlight-line"' : '') . '>';
                                        echo '<span class="line-number">' . $lineNumber . '</span>';
                                        echo htmlspecialchars($code);
                                        echo '</div>';
                                    }
                                    ?></pre>
                                </div>
                            <?php else: ?>
                                <div class="code-snippet">
                                    <pre>Source code not available</pre>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div id="snippet-tab" class="error-content">
            <div class="code-snippet">
                <pre><?php
                foreach ($snippet as $lineNumber => $code) {
                    $isHighlighted = $lineNumber === $line;
                    echo '<div' . ($isHighlighted ? ' class="highlight-line"' : '') . '>';
                    echo '<span class="line-number">' . $lineNumber . '</span>';
                    echo htmlspecialchars($code);
                    echo '</div>';
                }
                ?></pre>
            </div>
        </div>

        <div id="request-tab" class="error-content">
            <div class="request-data">
                <div class="request-data-header">
                    <div>Request URL</div>
                </div>
                <div class="request-data-content active">
                    <div class="code-snippet">
                        <pre><?= htmlspecialchars($requestData['method'] . ' ' . $requestData['url']) ?></pre>
                    </div>
                </div>
            </div>

            <div class="request-data">
                <div class="request-data-header">
                    <div>Headers</div>
                </div>
                <div class="request-data-content">
                    <table class="request-data-table">
                        <thead>
                            <tr>
                                <th>Header</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requestData['headers'] as $name => $value): ?>
                                <tr>
                                    <td><?= htmlspecialchars($name) ?></td>
                                    <td><?= htmlspecialchars($value) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="request-data">
                <div class="request-data-header">
                    <div>GET Parameters</div>
                </div>
                <div class="request-data-content">
                    <?php if (empty($requestData['get'])): ?>
                        <div class="code-snippet">
                            <pre>No GET parameters</pre>
                        </div>
                    <?php else: ?>
                        <table class="request-data-table">
                            <thead>
                                <tr>
                                    <th>Parameter</th>
                                    <th>Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requestData['get'] as $name => $value): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($name) ?></td>
                                        <td><?= htmlspecialchars(is_array($value) ? json_encode($value) : $value) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <div class="request-data">
                <div class="request-data-header">
                    <div>POST Parameters</div>
                </div>
                <div class="request-data-content">
                    <?php if (empty($requestData['post'])): ?>
                        <div class="code-snippet">
                            <pre>No POST parameters</pre>
                        </div>
                    <?php else: ?>
                        <table class="request-data-table">
                            <thead>
                                <tr>
                                    <th>Parameter</th>
                                    <th>Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requestData['post'] as $name => $value): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($name) ?></td>
                                        <td><?= htmlspecialchars(is_array($value) ? json_encode($value) : $value) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <div class="request-data">
                <div class="request-data-header">
                    <div>Session Data</div>
                </div>
                <div class="request-data-content">
                    <?php if (empty($requestData['session'])): ?>
                        <div class="code-snippet">
                            <pre>No session data</pre>
                        </div>
                    <?php else: ?>
                        <table class="request-data-table">
                            <thead>
                                <tr>
                                    <th>Key</th>
                                    <th>Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requestData['session'] as $name => $value): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($name) ?></td>
                                        <td><?= htmlspecialchars(is_array($value) ? json_encode($value) : $value) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div id="env-tab" class="error-content">
            <div class="env-info">
                <div class="env-item">
                    <span class="env-item-label">PHP Version</span>
                    <span class="env-item-value"><?= htmlspecialchars($env['php_version']) ?></span>
                </div>
                <div class="env-item">
                    <span class="env-item-label">Framework Version</span>
                    <span class="env-item-value"><?= htmlspecialchars($env['framework_version']) ?></span>
                </div>
                <div class="env-item">
                    <span class="env-item-label">Operating System</span>
                    <span class="env-item-value"><?= htmlspecialchars($env['os']) ?></span>
                </div>
                <div class="env-item">
                    <span class="env-item-label">Memory Limit</span>
                    <span class="env-item-value"><?= htmlspecialchars($env['memory_limit']) ?></span>
                </div>
                <div class="env-item">
                    <span class="env-item-value"><?= htmlspecialchars($env['memory_limit']) ?></span>
                </div>
                <div class="env-item">
                    <span class="env-item-label">Max Execution Time</span>
                    <span class="env-item-value"><?= htmlspecialchars($env['max_execution_time']) ?> seconds</span>
                </div>
            </div>
        </div>
    </div>

    <script><?= $jsScripts ?></script>
</body>
</html>