<?php

abstract class Reloj {
    private $h = 0;
	private $m = 0;
	private $s = 0;

    function now() {
		$date = new DateTime();
		$h = (int)$date->format('H');
		$m = (int)$date->format('i');
		$s = (int)$date->format('s');
    }

    abstract protected function getGastoEnergetico($cantidadSegundos);
}

class RelojEstandar extends Reloj {

    public function getGastoEnergetico($cantidadSegundos) {

        $consumos = array(
            "0" => 6,
            "1" => 2,
            "2" => 6,
            "3" => 5,
            "4" => 4,
            "5" => 5,
            "6" => 6,
            "7" => 3,
            "8" => 7,
            "9" => 6
        );

        // creo hora de inicio y seteo las 00:00:00
        $horaInicio = new DateTime();
        $horaInicio->setTime(0,0,0);

        // seteo hora fin con segundos ingresados => 00:00:00 + segundos
        $horaFin = new DateTime();
        $horaFin->setTime(0,0,0);
        $horaFin->modify( "+" . $cantidadSegundos .' seconds');

        $total = 0;

        do {

            // obtengo la hora formateada 00:00:00
            $horaFormateada = $horaFin->format('H:i:s');

            // remuevo dos puntos 000000
            $horaSinSeparador = str_replace(":", "", $horaFormateada); // string

            // trasnformo hora en un array de caracteres
            $horaSeparadaPorCaracter = str_split($horaSinSeparador);

            // variable para guardar suma parcial
            $totalParcial = 0;

            // por cada vuelta sumo todos los caracteres
            foreach($horaSeparadaPorCaracter as $numeroIndividual) {
                $totalParcial += $consumos[$numeroIndividual];
            }

            // guardo en total la suma parcial
            $total += $totalParcial;

            // descomentar para ver cuanto se calcula por vuelta
            // echo "HORA : " . $formated  . " - PARCIAL : " . $totalParcial . "\n";

            // resto un segundo
            $horaFin->modify( '-1 seconds');

            // repito hasta llegar a la hora de inicio
        } while($horaFin >= $horaInicio);

        // muestro total por pantalla
        //  echo "TOTAL : " . $total;

        // return "Reponse: " . $total . "\n";
        return $total;

    }

}

class RelojPremim extends Reloj {

    private static $totalParcial = 0;

    private static function sumarParcial($valor) {
        // echo "parcial : " . self::$totalParcial . " - valor : " . $valor . "\n";
        self::$totalParcial += $valor;
    }


	public function getGastoEnergetico($cantidadSegundos) {

        $consumos = array(
            "0" => 1, // cambia depende del anterior
            "1" => 0,
            "2" => 4,
            "3" => 1,
            "4" => 1,
            "5" => 2,
            "6" => 1,
            "7" => 1,
            "8" => 4,
            "9" => 0
        );

        // creo hora de inicio y seteo las 00:00:00
        $horaInicio = new DateTime();
        $horaInicio->setTime(0,0,0);

        // seteo hora fin con segundos ingresados => 00:00:00 + segundos
        $horaFin = new DateTime();
        $horaFin->setTime(0,0,0);
        $horaFin->modify( "+" . $cantidadSegundos .' seconds');
        // voy guardando el previo para saber cuanto sumar
        $prev = str_split("000000");

        // inicializo por las dudas
        self::$totalParcial = 0;
    
        do {
            // obtengo la hora formateada 00:00:00
            $horaFormateada = $horaInicio->format('H:i:s');

            // remuevo dos puntos 000000
            $horaSinSeparador = str_replace(":", "", $horaFormateada); // string

            // trasnformo hora en un array de caracteres
            $h = str_split($horaSinSeparador);

            // echo "HORA : " . $horaInicio->format("H:i:s") . "\n";

            // se puede mejorar este if preguntando si cambio de dia, si pasa esto esta condicion no se tiene que aplicar
            if ($horaSinSeparador == "000000") {
                // por 00:00:00 siempre es 36w de consumo a menos que cambie de dia
                self::$totalParcial = 36;
            } else {

                // reicicializo sumaParcial
                // ______ HORA ______
                // [0]0 00 00 => primer caracter hora
                // si el anterior era 0 pasa a 1 entonces 0w consumo porque solo apaga sectores
                // si son iguales , no cambio y no hay consumo
                if( $prev[0] == "0" || $prev[0] == $h[0] ) {
                    self::sumarParcial(0);
                } elseif( $prev[0] == "2" ) {
                    self::sumarParcial(2); // si el anterior era 2 significa que vuelve a 0 el consumo es 2w
                } else {
                    self::sumarParcial($consumos[$h[0]]); // si no es ninguna de las dos condiciones anteriores busco el consumo en la lista de consumos
                }

                // 0[0] 00 00 => segundo caracter hora
                if( $prev[1] == "0" || $prev[1] == $h[1] ) {
                    self::sumarParcial(0);
                } elseif( $prev[1] == "3" && $h[1] == "0" ) {
                    self::sumarParcial(2); // esto solo aplica a la hora 23 ya que pasa a 00 , apaga un sector y prende otros dos para hacer el 0 desde el 3
                } else {
                    self::sumarParcial($consumos[$h[1]]); // para todos los demas cambios busco el consumo
                }

                // ______ MIN ______
                // 00 [0]0 00 => primer caracter min
                // min -> 59 _ del 5 al 0 consumo 2w
                // ciclos 0 a 5
                if( $prev[2] == "0" || $prev[2] == $h[2] ) {
                    self::sumarParcial(0);
                } elseif( $prev[2] == "5" && $h[2] == "0" ) {
                    self::sumarParcial(2);
                } else {
                    self::sumarParcial($consumos[$h[2]]);
                }

                // ------------------------------------
                // 00 0[0] 00 => segundo caracter min - 3
                // ciclos de 0 a 9
                if( $prev[3] == "0" || $prev[3] == $h[3] ) {
                    self::sumarParcial(0);
                } else {
                    self::sumarParcial($consumos[$h[3]]);
                }
                
                // ______ SEG _______
                // 00 00 [0]0 => primer caracter seg - 4
                // ciclos 0 a 5
                if( $prev[4] == "0" || $prev[4] == $h[4] ) {
                    self::sumarParcial(0);
                } elseif( $prev[4] == "5") {
                    self::sumarParcial(2);
                } else {
                    self::sumarParcial($consumos[$h[4]]);
                }

                // hacen todo el ciclo completo de 0 al 9
                if( $prev[5] == "0" || $prev[5] == $h[5] ) {
                    self::sumarParcial(0);
                } else {
                    self::sumarParcial($consumos[$h[5]]);
                }

                // actualizo los todos los caracteres
                $prev[0] = $h[0];
                $prev[1] = $h[1];
                $prev[2] = $h[2];
                $prev[3] = $h[3];
                $prev[4] = $h[4];
                $prev[5] = $h[5];
            }

            // echo "\n PARCIAL : " . self::$totalParcial . "\n";
            $horaInicio->modify( "+1 seconds");

        } while ( $horaInicio <= $horaFin );

        return self::$totalParcial;

    }

}

echo "\n__ Reloj Estandar __\n";
$e = new RelojEstandar();
echo "gasto : " . $e->getGastoEnergetico(0); echo "uW \n";
echo "gasto : " . $e->getGastoEnergetico(4); echo "uW \n";

echo "\n__ Reloj Premium __ \n";
$p = new RelojPremim();
echo "gasto : " . $p->getGastoEnergetico(0); echo "uW \n";
echo "gasto : " . $p->getGastoEnergetico(4); echo "uW \n";

$segundosEnUnDia = 86399;

$consumoEstandardUnDia = $e->getGastoEnergetico($segundosEnUnDia);
$consumoPremiumUnDia = $p->getGastoEnergetico($segundosEnUnDia);

echo "\n";
echo "Consumo en un dia PREMIUM   : " . $consumoPremiumUnDia . "uW \n";
echo "Consumo en un dia ESTANDARD : " . $consumoEstandardUnDia . "uW \n";
echo "Ahorro de PREMIUM vs ESTANDARD: " . ($consumoEstandardUnDia - $consumoPremiumUnDia) . "uW\n";


?>