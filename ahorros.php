<?php

class Simulacion
{

    function __construct($gasto, $cadaNdias, $tasa, $plazo = 30, $tipo = "caja_ahorro", $montoDPF = 0)
    {
        $this->gasto = $gasto;
        $this->cadaNdias = $cadaNdias;
        $this->tasa = $tasa;
        $this->plazo = $plazo;
        $this->tipo = $tipo;
        $this->montoDPF = $montoDPF;
    }

    /**
     * @var DateTime $time 
     */
    private $time;

    /**
     * @var DateTime[] $time 
     */
    private $lastCalls = [];

    /**
     * @var float $capital 
     */
    private $capital = 0;

    /**
     * @var float $capital 
     */
    private $interesDiario = 0;

    /**
     * @var float $capital 
     */
    private $ganaciaDiaria = 0;

    /**
     * @var float $capital 
     */
    public $gastoTotal = 0;
    public $gananciaDPF = 0;
    public $capitalDPF = 0;
    public $ganaciaAhorros = 0;

    /**
     * Tasa Nominal Anual en tanto por 1
     *
     * @param float $TEA en en tanto por 1
     * @return float
     */
    function TNA($TEA)
    {
        return (pow((1 + $TEA), (1 / 12)) - 1) * 12;
    }

    function gasto()
    {
        $this->cadaNDias($this->cadaNdias,
            function () {
            if ($this->capital > $this->gasto) {
                if (ECHO_DEBUG)
                    echo "sacar para gasto: $this->gasto\n";
                $this->setCapital($this->capital - $this->gasto);
                $this->gastoTotal += $this->gasto;
            }
        });
    }

    function interesDiario()
    {
        $this->ganaciaDiaria += $this->capital * $this->interesDiario;
    }

    function salario()
    {
        $this->cadaMes(function () {
            if (ECHO_DEBUG)
                echo "pago salario\n";
            $this->setCapital($this->capital + 6900);
        });
    }

    function capitalizar()
    {
        if ($this->tipo === 'caja_ahorro') {
            $this->cadaMes(function () {
                if (ECHO_DEBUG)
                    echo "pago interes $this->ganaciaDiaria\n";
                $this->setCapital($this->capital + $this->ganaciaDiaria);
                $this->ganaciaAhorros += $this->ganaciaDiaria;
                $this->ganaciaDiaria = 0;
            });
        }
        if ($this->tipo === 'dpf') {
            $this->cadaNdias($this->plazo,
                function () {
                if (ECHO_DEBUG)
                    echo "ganancia DPF $this->gananciaDPF\n";
                $montoDPF = min($this->montoDPF, $this->capital);
                $this->capitalDPF += $montoDPF + $this->gananciaDPF;
                $this->ganaciaAhorros += $this->gananciaDPF;
                $this->gananciaDPF = $this->capitalDPF * $this->tasa / 100 / 360 * $this->plazo;
                if (ECHO_DEBUG)
                    echo "agregar capital DPF $montoDPF\n";
                $this->setCapital($this->capital - $montoDPF);
            });
        }
    }

    function cada($caller, closure $condition, closure $callback)
    {
        $this->lastCalls[$caller] = !isset($this->lastCalls[$caller]) ? clone $this->time : $this->lastCalls[$caller];
        $diff = $this->time->diff($this->lastCalls[$caller]);
        if ($condition($diff, $this->time, $this->lastCalls[$caller])) {
            $this->lastCalls[$caller] = clone $this->time;
            $callback();
        }
    }

    private function cadaMes(closure $callback)
    {
        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        $this->cada($caller,
            function (DateInterval $diff, DateTime $a, DateTime $b) {
            return $a->format('m') != $b->format('m');
        }, $callback);
    }

    private function cadaNdias($nDias, closure $callback)
    {
        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'];
        $this->cada($caller,
            function (DateInterval $diff, DateTime $a, DateTime $b) use($nDias) {
            return $diff->days >= $nDias;
        }, $callback);
    }

    function __invoke(DateTime $initialTime, DateTime $finalTime)
    {
        $this->time = clone $initialTime;
        $this->interesDiario = $this->TNA($this->tasa / 100) / 360;
        $interval = new DateInterval('P1D');

        if (ECHO_DEBUG)
            echo '$this->interesDiario: ', $this->interesDiario, "\n";
        $daterange = new DatePeriod($initialTime, $interval, $finalTime);
        foreach ($daterange as $date) {
            $this->time = $date;
            $this->salario();
            if ($this->tipo === 'caja_ahorro') {
                $this->interesDiario();
            }
            $this->capitalizar();
            $this->gasto();
        }
        return $this->capital;
    }

    function setCapital($capital)
    {
        $this->capital = $capital;
        if (ECHO_DEBUG)
            echo $this->time->format("Y-m-d"), " capital: $this->capital ", "\n";
    }
}

define('ECHO_DEBUG', false);
$start = new DateTime('2018-12-31 00:00:00');
$end = new DateTime('2019-12-31 00:00:00');

for ($gastoTotal = 1000; $gastoTotal <= 6000; $gastoTotal += 500) {
    for ($days = 1; $days <= 30; $days++) {
        $gasto = round($gastoTotal / 30 * $days);
        $simulacion = new Simulacion($gasto, $days, 3.5);
        $capital = $simulacion($start, $end);
        //echo "cada $days dias gasto Bs. $gasto => gasto total=$simulacion->gastoTotal => capital final: Bs.", $capital, "\n";
        //echo $gastoTotal, "\t", $days, "\t", $gasto, "\t", $simulacion->gastoTotal, "\t", $capital, "\t", $capital * $simulacion->gastoTotal / 100000000, "\t", $simulacion->ganaciaAhorros, "\n";
        echo $gastoTotal, "\t", $days, "\t", $gasto, "\t", $simulacion->gastoTotal, "\t", $capital, "\t", $simulacion->ganaciaAhorros * $simulacion->gastoTotal / 100000000, "\t", $simulacion->ganaciaAhorros, "\n";
    }
}

$plazo = 30;
$tasa = 0.12;
for ($gastoTotal = 1000; $gastoTotal <= 6000; $gastoTotal += 500) {
    for ($days = 1; $days <= 30; $days++) {
        $gasto = round($gastoTotal / 30 * $days);
        $montoDPF = (6500 - $gasto) * $plazo / 30;
        $simulacion = new Simulacion($gasto, $days, $tasa, $plazo, 'dpf',
            $montoDPF);
        $capital = $simulacion($start, $end);
        //echo "cada $days dias gasto Bs. $gasto => gasto total=$simulacion->gastoTotal => capital final: Bs.", $capital, "\n";
        //echo $gastoTotal, "\t", $days, "\t", $gasto, "\t", $simulacion->gastoTotal, "\t", $capital + $simulacion->capitalDPF, "\t", ($capital + $simulacion->capitalDPF) * $simulacion->gastoTotal / 100000000, "\t", $simulacion->ganaciaAhorros, "\t", $montoDPF, "\n";
        echo $gastoTotal, "\t", $days, "\t", $gasto, "\t", $simulacion->gastoTotal, "\t", $capital + $simulacion->capitalDPF, "\t", $simulacion->ganaciaAhorros * $simulacion->gastoTotal / 100000000, "\t", $simulacion->ganaciaAhorros, "\t", $montoDPF, "\n";
    }
}
