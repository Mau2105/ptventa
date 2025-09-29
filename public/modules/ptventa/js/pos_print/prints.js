/* ================================================
   Función para detectar impresora automáticamente
================================================ */
async function getDefaultPrinter(conector) {
    const impresoras = await conector.obtenerImpresoras();
    if (impresoras.length === 0) {
        toastr.error('No se encontraron impresoras instaladas.', 'Error de impresión');
        throw new Error("No hay impresoras instaladas");
    }
    return impresoras[0]; // Usa la primera impresora disponible
}

/* ================================================
   Imprimir venta realizada
================================================ */
async function print_sale(movement) {
    const separarCadenaEnArregloSiSuperaLongitud = (cadena, maximaLongitud) => {
        const resultado = [];
        let indice = 0;
        while (indice < cadena.length) {
            const pedazo = cadena.substring(indice, indice + maximaLongitud);
            indice += maximaLongitud;
            resultado.push(pedazo);
        }
        return resultado;
    };

    const dividirCadenasYEncontrarMayorConteoDeBloques = (contenidosConMaximaLongitud) => {
        let mayorConteoDeCadenasSeparadas = 0;
        const cadenasSeparadas = [];
        for (const contenido of contenidosConMaximaLongitud) {
            const separadas = separarCadenaEnArregloSiSuperaLongitud(contenido.contenido, contenido.maximaLongitud);
            cadenasSeparadas.push({ separadas, maximaLongitud: contenido.maximaLongitud });
            if (separadas.length > mayorConteoDeCadenasSeparadas) {
                mayorConteoDeCadenasSeparadas = separadas.length;
            }
        }
        return [cadenasSeparadas, mayorConteoDeCadenasSeparadas];
    };

    const tabularDatos = (cadenas, relleno, separadorColumnas) => {
        const [arreglos, mayorConteo] = dividirCadenasYEncontrarMayorConteoDeBloques(cadenas);
        let indice = 0;
        const lineas = [];
        while (indice < mayorConteo) {
            let linea = "";
            for (const contenidos of arreglos) {
                let cadena = "";
                if (indice < contenidos.separadas.length) {
                    cadena = contenidos.separadas[indice];
                }
                if (cadena.length < contenidos.maximaLongitud) {
                    cadena = cadena + relleno.repeat(contenidos.maximaLongitud - cadena.length);
                }
                linea += cadena + separadorColumnas;
            }
            lineas.push(linea);
            indice++;
        }
        return lineas;
    };

    // Estructura de tabla
    const maximaLongitudItem = 2,
        maximaLongitudNombre = 22,
        maximaLongitudCantidad = 4,
        maximaLongitudPrecio = 7,
        maximaLongitudSubtotal = 8,
        relleno = " ",
        separadorColumnas = "|";

    const obtenerLineaSeparadora = () => {
        const lineas = tabularDatos(
            [
                { contenido: "-", maximaLongitud: maximaLongitudItem },
                { contenido: "-", maximaLongitud: maximaLongitudNombre },
                { contenido: "-", maximaLongitud: maximaLongitudCantidad },
                { contenido: "-", maximaLongitud: maximaLongitudPrecio },
                { contenido: "-", maximaLongitud: maximaLongitudSubtotal },
            ],
            "-",
            "+"
        );
        return lineas[0] || "";
    };

    let tabla = obtenerLineaSeparadora() + "\n";
    const lineasEncabezado = tabularDatos(
        [
            { contenido: "#", maximaLongitud: maximaLongitudItem },
            { contenido: "Producto", maximaLongitud: maximaLongitudNombre },
            { contenido: "Cant", maximaLongitud: maximaLongitudCantidad },
            { contenido: "V.Unit", maximaLongitud: maximaLongitudPrecio },
            { contenido: "Subtotal", maximaLongitud: maximaLongitudSubtotal },
        ],
        relleno,
        separadorColumnas
    );
    for (const linea of lineasEncabezado) {
        tabla += linea + "\n";
    }
    tabla += obtenerLineaSeparadora() + "\n";

    const processed_elements = new Set();
    let iteration_group = 0;

    for (const d of movement.movement_details) {
        const element_id = d.inventory.element_id;
        if (!processed_elements.has(element_id)) {
            processed_elements.add(element_id);
            iteration_group++;
            let total_amount = 0;
            for (const aux_d of movement.movement_details) {
                if (aux_d.inventory.element_id == element_id) {
                    total_amount += aux_d.amount;
                }
            }

            const lineas = tabularDatos(
                [
                    { contenido: iteration_group.toString(), maximaLongitud: maximaLongitudItem },
                    { contenido: d.inventory.element.name + " (" + d.inventory.element.measurement_unit.name + ")", maximaLongitud: maximaLongitudNombre },
                    { contenido: " " + total_amount.toString(), maximaLongitud: maximaLongitudCantidad },
                    { contenido: priceFormat(d.price), maximaLongitud: maximaLongitudPrecio },
                    { contenido: priceFormat(total_amount * d.price), maximaLongitud: maximaLongitudSubtotal },
                ],
                relleno,
                separadorColumnas
            );
            for (const linea of lineas) {
                tabla += linea + "\n";
            }
            tabla += obtenerLineaSeparadora() + "\n";
        }
    }

    const customer = movement.movement_responsibilities.find(wm => wm.role === "CLIENTE").person;
    const seller = movement.movement_responsibilities.find(wm => wm.role === "VENDEDOR").person;

    const document_type_abbreviations = {
        'Cédula de ciudadanía': 'CC',
        'Tarjeta de identidad': 'TI',
        'Cédula de extranjería': 'CE',
        'Pasaporte': 'PP',
        'Documento nacional de identidad': 'DNI',
        'Registro civil': 'RC'
    };

    try {
        const conector = new ConectorPluginV3();
        const impresora = await getDefaultPrinter(conector);

        const respuesta = await conector
            .Iniciar()
            .EstablecerTamañoFuente(1, 1)
            .EstablecerAlineacion(ConectorPluginV3.ALINEACION_CENTRO)
            .TextoSegunPaginaDeCodigos(2, "cp850", "CENTRO DE FORMACIÓN AGROINDUSTRIAL\n")
            .EscribirTexto("Nit 899.99934-1\n")
            .DeshabilitarElModoDeCaracteresChinos()
            .TextoSegunPaginaDeCodigos(2, "cp850", "Producción de Centro - SENA Empresa\n")
            .EscribirTexto("La Angostura\n")
            .EscribirTexto("Art 17 Decreto 1001 de 1997\n")
            .EscribirTexto("------------------------------------------------\n")
            .EstablecerEnfatizado(true)
            .TextoSegunPaginaDeCodigos(2, "cp850", "Factura de venta N°: " + movement.voucher_number + "\n")
            .EstablecerEnfatizado(false)
            .EscribirTexto("------------------------------------------------\n")
            .EstablecerAlineacion(ConectorPluginV3.ALINEACION_IZQUIERDA)
            .EscribirTexto("Fecha: " + movement.registration_date + "\n")
            .EscribirTexto("Cliente: " + customer.first_name + " " + customer.first_last_name + "\n")
            .EscribirTexto("Identificación: " + (document_type_abbreviations[customer.document_type] || customer.document_type) + "-" + customer.document_number + "\n")
            .EscribirTexto("Vendedor: " + seller.first_name + " " + seller.first_last_name + "\n")
            .TextoSegunPaginaDeCodigos(2, "cp850", tabla)
            .EstablecerAlineacion(ConectorPluginV3.ALINEACION_DERECHA)
            .EscribirTexto("TOTAL: " + priceFormat(movement.price) + " |\n")
            .EstablecerAlineacion(ConectorPluginV3.ALINEACION_CENTRO)
            .EscribirTexto("Muchas gracias por su compra\n")
            .TextoSegunPaginaDeCodigos(2, "cp850", "¡Vuelva pronto!")
            .Feed(4)
            .Corte(1)
            .imprimirEn(impresora);

        if (respuesta !== true) {
            toastr.error(respuesta, 'Error de impresión');
            return false;
        }
        return true;
    } catch (error) {
        toastr.error(error.message, 'Error de impresión');
        return false;
    }
}
