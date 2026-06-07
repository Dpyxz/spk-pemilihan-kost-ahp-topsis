# PROYEK SPK PEMILIHAN TEMPAT KOST
# METODE AHP DAN TOPSIS

kriteria <- c(
  "Harga",
  "Jarak",
  "Fasilitas",
  "WiFi",
  "Keamanan"
)

hitung_ahp <- function(M, nama){
  
  cat("\n")
  cat("\n", nama)
  cat("\n")
  
  print(round(M,3))
  
  jumlah_kolom <- colSums(M)
  
  cat("\nJumlah Kolom:\n")
  print(round(jumlah_kolom,3))
  
  normalisasi <- M
  
  for(i in 1:ncol(M)){
    
    normalisasi[,i] <- M[,i] / jumlah_kolom[i]
    
  }
  
  cat("\nNormalisasi Matrix:\n")
  print(round(normalisasi,3))
  
  prioritas <- rowMeans(normalisasi)
  
  hasil_prioritas <- data.frame(
    Kriteria = kriteria,
    Prioritas = round(prioritas,3)
  )
  
  cat("\nBobot Prioritas:\n")
  print(hasil_prioritas)
  
  lambda_max <- sum(jumlah_kolom * prioritas)
  
  cat("\nLambda Max:\n")
  print(round(lambda_max,3))
  
  n <- nrow(M)
  
  CI <- (lambda_max - n)/(n - 1)
  
  cat("\nConsistency Index:\n")
  print(round(CI,3))
  
  RI <- 1.12
  
  cat("\nRandom Index:\n")
  print(RI)
  
  CR <- CI / RI
  
  cat("\nConsistency Ratio:\n")
  print(round(CR,3))
  
  if(CR < 0.1){
    
    cat("\nSTATUS : KONSISTEN\n")
    
  } else {
    
    cat("\nSTATUS : TIDAK KONSISTEN\n")
    
  }
  
  return(prioritas)
}

R1 <- matrix(c(
  
  1,    3,    5,    5,    1,
  1/3,  1,    3,    5,    1/3,
  1/5,  1/3,  1,    3,    1/3,
  1/5,  1/5,  1/3,  1,    1/5,
  1,    3,    3,    5,    1
  
), nrow=5, byrow=TRUE)

R2 <- matrix(c(
  
  1,    3,    5,    5,    3,
  1/3,  1,    3,    3,    1,
  1/5,  1/3,  1,    1,    1/3,
  1/5,  1/3,  1,    1,    1/3,
  1/3,  1,    3,    3,    1
  
), nrow=5, byrow=TRUE)

R3 <- matrix(c(
  
  1,    3,    5,    5,    5,
  1/3,  1,    3,    3,    3,
  1/5,  1/3,  1,    1,    1,
  1/5,  1/3,  1,    1,    1,
  1/5,  1/3,  1,    1,    1
  
), nrow=5, byrow=TRUE)

R4 <- matrix(c(
  
  1,    3,    5,    3,    1,
  1/3,  1,    1,    3,    1/3,
  1/5,  1,    1,    3,    1/3,
  1/3,  1/3,  1/3,  1,    1/3,
  1,    3,    3,    3,    1
  
), nrow=5, byrow=TRUE)

R5 <- matrix(c(
  
  1,    3,    5,    5,    3,
  1/3,  1,    3,    3,    1,
  1/5,  1/3,  1,    1,    1/3,
  1/5,  1/3,  1,    1,    1/3,
  1/3,  1,    3,    3,    1
  
), nrow=5, byrow=TRUE)

P1 <- hitung_ahp(R1, "RESPONDEN 1")
P2 <- hitung_ahp(R2, "RESPONDEN 2")
P3 <- hitung_ahp(R3, "RESPONDEN 3")
P4 <- hitung_ahp(R4, "RESPONDEN 4")
P5 <- hitung_ahp(R5, "RESPONDEN 5")

bobot_matrix <- rbind(P1,P2,P3,P4,P5)

geomean <- apply(
  bobot_matrix,
  2,
  function(x){
    
    prod(x)^(1/length(x))
    
  }
)

bobot_final <- geomean / sum(geomean)

cat("\nBOBOT FINAL AHP\n")

hasil_bobot <- data.frame(
  
  Kriteria = kriteria,
  Bobot = round(bobot_final,3)
  
)

print(hasil_bobot)

alternatif <- c(
  
  "Kost Mawar",
  "Kost Melati",
  "Kost Tulip",
  "Kost Anggrek",
  "Kost Sakura"
  
)

X <- matrix(c(
  
  800, 1, 4, 4, 4,
  650, 2, 3, 3, 4,
  900, 1, 5, 5, 5,
  700, 3, 4, 3, 3,
  750, 2, 4, 4, 5
  
), byrow=TRUE, nrow=5)

colnames(X) <- kriteria
rownames(X) <- alternatif

cat("\nMATRIX KEPUTUSAN\n")

print(X)

jenis <- c(
  
  "cost",
  "cost",
  "benefit",
  "benefit",
  "benefit"
  
)

R <- matrix(0, nrow=nrow(X), ncol=ncol(X))

for(j in 1:ncol(X)){
  
  pembagi <- sqrt(sum(X[,j]^2))
  
  R[,j] <- X[,j] / pembagi
  
}

colnames(R) <- kriteria
rownames(R) <- alternatif

cat("\nNORMALISASI TOPSIS\n")

print(round(R,3))

Y <- R

for(j in 1:ncol(R)){
  
  Y[,j] <- R[,j] * bobot_final[j]
  
}

colnames(Y) <- kriteria
rownames(Y) <- alternatif

cat("\nNORMALISASI TERBOBOT\n")

print(round(Y,3))

Aplus <- c()
Amin <- c()

for(j in 1:ncol(Y)){
  
  if(jenis[j] == "benefit"){
    
    Aplus[j] <- max(Y[,j])
    Amin[j] <- min(Y[,j])
    
  } else {
    
    Aplus[j] <- min(Y[,j])
    Amin[j] <- max(Y[,j])
  }
}

cat("\nSOLUSI IDEAL POSITIF\n")

print(round(Aplus,3))

cat("\nSOLUSI IDEAL NEGATIF\n")

print(round(Amin,3))

Dplus <- c()
Dmin <- c()

for(i in 1:nrow(Y)){
  
  Dplus[i] <- sqrt(sum((Y[i,] - Aplus)^2))
  
  Dmin[i] <- sqrt(sum((Y[i,] - Amin)^2))
  
}

V <- Dmin / (Dmin + Dplus)

hasil <- data.frame(
  
  Alternatif = alternatif,
  Dplus = round(Dplus,3),
  Dmin = round(Dmin,3),
  Preferensi = round(V,3)
  
)

hasil <- hasil[order(-hasil$Preferensi),]

hasil$Ranking <- 1:nrow(hasil)

cat("\nHASIL RANKING TOPSIS\n")

print(hasil)

barplot(
  
  hasil$Preferensi,
  
  names.arg = hasil$Alternatif,
  
  main = "Ranking Pemilihan Tempat Kost",
  
  col = "skyblue",
  
  las = 2
  
)

