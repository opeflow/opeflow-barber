function canaisSrgb(hexadecimal) {
    const numero = hexadecimal.replace('#', '')
    return [0, 2, 4].map((inicio) => parseInt(numero.slice(inicio, inicio + 2), 16) / 255)
}

function luminanciaRelativa(canais) {
    const linearizados = canais.map((canal) => canal <= 0.04045 ? canal / 12.92 : ((canal + 0.055) / 1.055) ** 2.4)
    return 0.2126 * linearizados[0] + 0.7152 * linearizados[1] + 0.0722 * linearizados[2]
}

function hexadecimal(canais) {
    return `#${canais.map((canal) => Math.round(canal * 255).toString(16).padStart(2, '0')).join('')}`.toUpperCase()
}

export function corDeTexto(hexadecimalCor) {
    const luminancia = luminanciaRelativa(canaisSrgb(hexadecimalCor))
    const contrasteEscuro = (luminancia + 0.05) / 0.05
    const contrasteClaro = 1.05 / (luminancia + 0.05)

    return contrasteEscuro >= contrasteClaro ? '#020617' : '#FFFFFF'
}

export function corLegivelEmFundoBranco(hexadecimalCor) {
    const canais = canaisSrgb(hexadecimalCor)
    if (1.05 / (luminanciaRelativa(canais) + 0.05) >= 4.5) return hexadecimalCor

    for (let escurecimento = 0.05; escurecimento <= 1; escurecimento += 0.05) {
        const cor = canais.map((canal) => canal * (1 - escurecimento))
        if (1.05 / (luminanciaRelativa(cor) + 0.05) >= 4.5) return hexadecimal(cor)
    }

    return '#020617'
}
