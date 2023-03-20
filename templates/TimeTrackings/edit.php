<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\TimeTracking $timeTracking
 * @var string[]|\Cake\Collection\CollectionInterface $tasks
 */

$this->assign('title', $this->Text->truncate($timeTracking->task->smartName, 20));
$customer = $timeTracking->task->service->project->customer;
?>
<div class="row">
    <div class="col">
        <h2>
            <i class="fa-solid fa-stopwatch"></i>️ <?= $timeTracking->task->name ?: "<i>" . $timeTracking->task->smartName . "</i>" ?>
        </h2>
        <div class="mb-3">
            <span>
                <?= $this->Html->link($customer->shortcut, ['controller' => 'Customers', 'action' => 'view', $customer->id], ['style' => 'color: ' . $customer->color, 'target' => '_blank']) ?> /
                <?= $this->Html->link($timeTracking->task->service->project->name, ['controller' => 'Projects', 'action' => 'view', $timeTracking->task->service->project->id], ['target' => '_blank']) ?> /
                <?= $this->Html->link($timeTracking->task->service->smartName, ['controller' => 'Services', 'action' => 'view', $timeTracking->task->service->id], ['target' => '_blank']) ?>
            </span>
        </div>
    </div>
    <div class="col-auto"><?= $this->Form->postLink(
            __('Delete'),
            ['action' => 'delete', $timeTracking->id],
            ['confirm' => __('Are you sure you want to delete #{0}?', $timeTracking->id), 'class' => 'text-danger']
        ) ?></div>
</div>
<div class="timeTrackings form content">
    <?= $this->Form->create($timeTracking, ["id" => "time-tracking-form"]) ?>
    <fieldset>
        <div class="stopwatch mb-3">
            <div class="input-group mb-3" style="max-width: 380px">
                <input id="stopwatch" type="text" class="form-control text-end" placeholder="" name="stopwatch" value="0"/>
                <button type="button" id="btn-start" class="btn btn-success">start
                </button>
                <button type="button" class="btn btn-warning btn-pause" id="btn-pause">
                    pause
                </button>
                <button type="button" class="btn btn-danger btn-reset" id="btn-reset">reset
                </button>
                <button type="button" class="btn btn-outline-secondary btn-modify" id="btn-minus">-5
                </button>
                <button type="button" class="btn btn-outline-secondary btn-modify" id="btn-plus">+5
                </button>
            </div>
            <div class="progress mb-2">
                <div id="progress-bar" class="progress-bar bg-secondary" role="progressbar" aria-label="Basic example"
                     style=""></div>
            </div>
        </div>
        <div class="row">
            <div class="col"><?= $this->Form->control('duration') ?></div>
        </div>
    </fieldset>
    <fieldset>
        <?php
        echo $this->Form->control('task.notes', ['label' => 'Task Notes', 'rows' => 15, 'class' => 'font-monospace']);
        ?>
    </fieldset>
    <div class="mb-3">
        <button class="btn btn-secondary">Submit</button>
        <button class="btn btn-primary" onclick="window.stopAndAdd(); return false;">
            Add Stopwatch and Submit
        </button>
    </div>
    <?= $this->Form->end() ?>
</div>
<script type="module">
    import {Stopwatch} from "/lib/cm-web-modules/stopwatch/Stopwatch.js";
    import {Notifications} from "../../webroot/lib/cm-web-modules/notifications/Notifications.js";

    const stopwatchOutput = document.getElementById("stopwatch")
    const durationInput = document.getElementById("duration")
    const form = document.getElementById("time-tracking-form")
    let notificationShown = false
    const notifications = new Notifications()
    const progressBar = document.getElementById("progress-bar")
    const pomodoroMinutes = 25
    const title = document.title
    let additionalMinutes = 0

    function updateTimerOutput() {
        const minutesExpired = stopwatch.secondsExpired() / 60 + additionalMinutes
        document.title = title + " ⏱️ " + (Math.round(minutesExpired * 100) / 100).toFixed(2)
        if (minutesExpired >= pomodoroMinutes && !notificationShown) {
            notificationShown = true
            notifications.show(pomodoroMinutes + " Minutes expired", "<?= h($timeTracking->task->name) ?>")
        }
        progressBar.style.width = minutesExpired / pomodoroMinutes * 100 + "%"
        stopwatchOutput.value = (Math.round(minutesExpired * 100) / 100).toFixed(2)
    }

    window.stopwatch = new Stopwatch({
        onTimeChanged: () => {
            updateTimerOutput()
        },
        onStateChanged: (running) => {
            if (running) {
                if (!progressBar.classList.contains("bg-primary")) {
                    progressBar.classList.add("bg-primary")
                    progressBar.classList.add("progress-bar-striped")
                    progressBar.classList.add("progress-bar-animated")
                    progressBar.classList.remove("bg-secondary")
                    progressBar.classList.remove("bg-secondary")
                }
            } else {
                if (progressBar.classList.contains("bg-primary")) {
                    progressBar.classList.remove("bg-primary")
                    progressBar.classList.remove("progress-bar-striped")
                    progressBar.classList.remove("progress-bar-animated")
                    progressBar.classList.add("bg-secondary")
                }
            }
        }
    })

    document.getElementById("btn-start").addEventListener("click", (event) => {
        event.preventDefault()
        start()
    })
    document.getElementById("btn-pause").addEventListener("click", (event) => {
        event.preventDefault()
        window.stopwatch.stop()
    })
    document.getElementById("btn-reset").addEventListener("click", (event) => {
        event.preventDefault()
        additionalMinutes = 0
        window.stopwatch.reset()
    })
    document.getElementById("btn-minus").addEventListener("click", (event) => {
        event.preventDefault()
        additionalMinutes = additionalMinutes - 5
        updateTimerOutput()
    })
    document.getElementById("btn-plus").addEventListener("click", (event) => {
        event.preventDefault()
        additionalMinutes = additionalMinutes + 5
        updateTimerOutput()
    })

    function start() {
        try {
            notifications.requestPermission()
        } catch (e) {
            console.log(e)
        }
        notificationShown = false
        window.stopwatch.start()
    }

    window.stopAndAdd = () => {
        if (!durationInput.value) {
            durationInput.value = 0
        }
        if (!stopwatchOutput.value) {
            stopwatchOutput.value = 0
        }
        const duration = (parseFloat(durationInput.value.replace(",", ".")) + parseFloat(stopwatchOutput.value) / 60).toFixed(2)
        durationInput.value = "" + duration
        console.log(durationInput.value)
        stopwatchOutput.value = 0
        form.submit()
    }
    updateTimerOutput()
    // stopwatch.start()
    /*
    if (<?= $timeTracking->duration > 0 ? "false" : "true" ?>) {
        stopwatch.start()
    }
    */
    // Allow TAB
    const textarea = document.querySelector('textarea')
    textarea.addEventListener('keydown', function (e) {
        if (e.key === 'Tab') {
            e.preventDefault();
            var start = this.selectionStart;
            var end = this.selectionEnd;

            // set textarea value to: text before caret + tab + text after caret
            this.value = this.value.substring(0, start) +
                "\t" + this.value.substring(end);

            // put caret at right position again
            this.selectionStart =
                this.selectionEnd = start + 1;
        }
    })
    textarea.style.tabSize = "4"
</script>
